<?php

$log_file = '/home/owncast/logs/owncast_recording.log';  // Path to log file
$recording_directory = '/home/owncast/recordings/'; // Directory to save recordings
$stream_url = 'http://myserver/hls/stream.m3u8';  // Replace with your Owncast stream URL

$filename = "owncast_live_" . date("Y-m-d_H-i-s");
define('TS_FILE', $recording_directory . $filename . ".ts");
define('RECORDING_FILE_PATH', '/home/owncast/logs/recording_file.txt');

// Read the incoming JSON data from Owncast's webhook
$data = json_decode(file_get_contents('php://input'), true);

// Log function to append to log file
function log_message($message)
{
    global $log_file;
    $log_entry = date('Y-m-d H:i:s') . ' - ' . $message . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Function to start FFmpeg recording
function start_recording($stream_url)
{
    $command = sprintf("ffmpeg -i '%s' -c copy -buffer_size 100M -threads 2 -reconnect 1 -reconnect_streamed 1 -reconnect_delay_max 2 '%s' > /home/owncast/logs/ffmpeg_output.log 2>&1 & echo $!", $stream_url, TS_FILE);
    shell_exec($command);
    log_message("Recording started: " . TS_FILE);

    file_put_contents(RECORDING_FILE_PATH, TS_FILE);
    return TS_FILE;
}

// Function to stop FFmpeg recording
function stop_recording()
{
    $ffmpeg_pid = shell_exec("pgrep ffmpeg");  // Find the FFmpeg process ID
    if ($ffmpeg_pid) {
        shell_exec("kill -9 $ffmpeg_pid");
        log_message("Recording stopped. FFmpeg PID: $ffmpeg_pid");
    } else {
        log_message("No FFmpeg process found.");
    }

    // Retrieve the recorded .ts file name
    if (file_exists(RECORDING_FILE_PATH)) {
        $ts_file = file_get_contents(RECORDING_FILE_PATH);
    } else {
        log_message("Recording file not found.");
        return;
    }
    $mp3_file = str_replace('.ts', '.mp3', $ts_file);
    $mp4_file = str_replace('.ts', '.mp4', $ts_file);

    $command_mp3 = sprintf("ffmpeg -i %s -threads 2 -vn -acodec libmp3lame -b:a 128k %s > /home/owncast/logs/ffmpeg_convert_output.log 2>&1 &", $ts_file, $mp3_file);
    $command_mp4 = sprintf("ffmpeg -i %s -c:v libx264 -threads 2 -c:a aac %s > /home/owncast/logs/ffmpeg_convert_output.log 2>&1 &", $ts_file, $mp4_file);
    shell_exec($command_mp3);
    log_message("Conversion started: " . $mp3_file);

    sleep(5);
    if (file_exists($mp3_file)) {
        log_message("Conversion successful.");
    } else {
        log_message("Conversion failed.");
    }
}

// Function to check Stream availability
function wait_for_stream($stream_url, $max_attempts = 10, $interval = 3)
{
    $attempt = 0;
    while ($attempt < $max_attempts) {
        $headers = get_headers($stream_url, 1);
        if (strpos($headers[0], '200') !== false) {
            return true; // Stream is available
        }
        sleep($interval); // Wait before checking again
        $attempt++;
    }
    return false; // Stream not available after max_attempts
}

// Check the type of event received from the webhook
if (isset($data['type'])) {
    if ($data['type'] === 'STREAM_STARTED') {
        // Stream started: Begin recording
        log_message("Stream started event received.");
        if (wait_for_stream($stream_url)) {
            $output_file = start_recording($stream_url);
        } else {
            log_message("Stream not available after waiting.");
        }
    } elseif ($data['type'] === 'STREAM_STOPPED') {
        // Stream stopped: Stop recording
        log_message("Stream stopped event received.");
        stop_recording();
    } else {
        log_message("Unknown event received: " . $data['type']);
    }
} else {
    log_message("No valid event received.");
}

// Respond to the webhook to confirm receipt of the event
http_response_code(200);
echo "Webhook received.";
