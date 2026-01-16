# GymFit Live Chat Setup Guide

## Overview
This setup enables real-time chat messaging between clients and trainers using WebSocket technology (Socket.IO).

## Prerequisites
- Node.js (v14 or higher)
- npm (Node Package Manager)
- MySQL database (already set up)

## Installation Steps

### 1. Install Node.js (if not already installed)
- Download from: https://nodejs.org/
- Install the LTS version
- Verify installation: `node --version` and `npm --version`

### 2. Navigate to chat-server directory
```bash
cd e:\xampp\htdocs\gymfit\chat-server
```

### 3. Install dependencies
```bash
npm install
```

### 4. Configure database connection
Edit the `.env` file in the `chat-server` folder:
```
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=gymfit
PORT=3000
```

Update with your actual MySQL credentials if needed.

### 5. Start the chat server
```bash
npm start
```

Expected output:
```
Chat server running on port 3000
Connected to database
```

### 6. Access the application
- Open your browser and go to: http://localhost/gymfit
- Login as a client or trainer
- Go to Chat section
- The server should automatically connect to the WebSocket

## Features

✅ **Real-Time Messaging**
- Messages appear instantly to both parties
- No need to refresh or wait for polling

✅ **Typing Indicator**
- Shows when the other person is typing
- Automatically disappears after 1 second of inactivity

✅ **User Status**
- Shows online/offline status
- Tracks active conversations

✅ **Message History**
- All messages are saved to database
- History loads when opening old conversations

## Troubleshooting

### Issue: "Cannot connect to server"
**Solution:** 
- Ensure Node.js server is running (`npm start`)
- Check if port 3000 is not already in use
- Check firewall settings

### Issue: "Messages not sending"
**Solution:**
- Open browser DevTools (F12) → Console
- Check for errors in console
- Verify chat-server is running
- Ensure database connection is working

### Issue: "CORS Error"
**Solution:**
- This is already configured in the server
- Check browser console for specific origin errors
- Restart the Node server if changes were made

## Key Files Modified

1. **dashboard/client/client_chat.php**
   - Added Socket.IO client integration
   - Real-time message handling

2. **dashboard/trainer/trainer_chat.php**
   - Added Socket.IO client integration
   - Real-time message handling

3. **dashboard/client/ajax/get_socket_auth.php** (NEW)
   - Authentication for WebSocket connections

4. **dashboard/trainer/ajax/get_socket_auth.php** (NEW)
   - Authentication for WebSocket connections

5. **chat-server/server.js** (NEW)
   - Node.js server with Socket.IO
   - Handles real-time messaging

## Testing Live Chat

1. Open two browser windows (or incognito mode)
2. Login as different users in each window
3. Start a conversation
4. Send a message from one account
5. **Message appears instantly in the other account** ✓

## Architecture

```
User A (Browser)
    ↓
    Socket.IO Connection
    ↓
Node.js Server (localhost:3000)
    ↓
    MySQL Database
    ↓
User B (Browser)
    ↑
    Socket.IO Connection
    ↑
```

## API Events

### Client → Server
- `join_chat` - User enters conversation
- `send_message` - Send a message
- `typing` - User is typing
- `leave_chat` - User leaves conversation

### Server → Client
- `new_message` - New message received
- `user_typing` - User is typing
- `user_online` - User came online
- `user_offline` - User went offline

## Production Notes

For production deployment:
1. Use a process manager like PM2: `npm install -g pm2`
2. Run: `pm2 start server.js`
3. Update server URL in chat PHP files from `http://localhost:3000` to your production domain
4. Set up SSL/TLS for secure WebSocket (WSS)
5. Configure proper CORS settings for your domain

