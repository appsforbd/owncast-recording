# Owncast Recording Webhook

Automatic recording for Owncast live streams using FFmpeg.

## Overview

This script enables automatic recording of live streams from Owncast by utilizing FFmpeg and webhooks. It listens for stream events and starts or stops recording accordingly.

## Steps to Set Up Automatic Recording with FFmpeg

### 1. Enable Webhooks in Owncast

Owncast provides webhooks that notify you of events such as when a stream starts or stops. You can use the **STREAM_STARTED** and **STREAM_STOPPED** webhooks to trigger the FFmpeg recording.

- **Access the Owncast Admin Panel:**
  - URL: `http://myserver:8080/admin`

- **Navigate to:**
  - **Server Config** > **Webhooks**

- **Add a New Webhook:**
  - Provide a URL for the server where you’ll host your recording script (e.g., `http://myserver/owncast_recording.php`).
  - Select the events that will be sent to this webhook:
    - When a stream starts
    - When a stream stops

### 2. Upload the Webhook Listener on Your Server

Upload the webhook listener script to your server. Ensure the webhook URL points to the server where your **owncast_recording.php** script is located.

### 3. Configure Recording Settings

In the **owncast_recording.php** file, configure any necessary recording settings, such as file paths and FFmpeg options.

### 4. Test the Setup

Start a stream in Owncast and monitor the logs to verify that recording starts and stops correctly according to the stream events.

## Example Log Output

You can monitor the output in the designated log file to confirm that the recording process is functioning as intended.

```
2024-10-11 12:04:07 - Stream started event received.
2024-10-11 12:04:07 - Recording started: /path/to/recordings/owncast_live_2024-10-11_12-04-07.ts
2024-10-11 12:04:51 - Stream stopped event received.
2024-10-11 12:04:51 - Recording stopped. FFmpeg PID: <pid>
```

## Additional Notes

- Ensure that your server has FFmpeg installed and accessible via command line.
- You may need to adjust permissions and configurations on your server for the script to execute properly.
