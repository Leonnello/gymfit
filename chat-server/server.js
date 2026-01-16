const express = require('express');
const http = require('http');
const socketIO = require('socket.io');
const mysql = require('mysql');
const cors = require('cors');
require('dotenv').config();

const app = express();
const server = http.createServer(app);
const io = socketIO(server, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"]
  }
});

app.use(cors());
app.use(express.json());

// MySQL connection
const connection = mysql.createConnection({
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASS || '',
  database: process.env.DB_NAME || 'gymfit'
});

connection.connect((err) => {
  if (err) {
    console.error('Database connection failed:', err);
    return;
  }
  console.log('Connected to database');
});

// Store active users
const activeUsers = new Map();

io.on('connection', (socket) => {
  console.log('New user connected:', socket.id);

  // User joins chat
  socket.on('join_chat', (data) => {
    const { user_id, conversation_id } = data;
    
    // Store user connection
    activeUsers.set(user_id, {
      socket_id: socket.id,
      conversation_id: conversation_id
    });

    // Join a room for this conversation
    socket.join(`conversation_${conversation_id}`);
    console.log(`User ${user_id} joined conversation ${conversation_id}`);

    // Broadcast user is typing/online
    socket.to(`conversation_${conversation_id}`).emit('user_online', {
      user_id: user_id,
      status: 'online'
    });
  });

  // Handle new messages
  socket.on('send_message', (data, callback) => {
    const { conversation_id, sender_id, message } = data;

    // Validate input
    if (!conversation_id || !sender_id || !message) {
      console.error('Invalid message data');
      if (callback) callback({ success: false, error: 'Invalid data' });
      return;
    }

    // Save to database
    const query = `
      INSERT INTO messages (conversation_id, sender_id, message, created_at)
      VALUES (?, ?, ?, NOW())
    `;
    
    connection.query(query, [conversation_id, sender_id, message], (err, result) => {
      if (err) {
        console.error('Database error:', err);
        if (callback) callback({ success: false, error: 'Database error' });
        return;
      }

      // Update conversation preview
      const updateQuery = `
        UPDATE conversations
        SET last_message = ?, last_message_at = NOW()
        WHERE id = ?
      `;
      connection.query(updateQuery, [message, conversation_id], (err) => {
        if (err) console.error('Update error:', err);
      });

      // Send success acknowledgment
      if (callback) {
        callback({ success: true, message_id: result.insertId });
      }

      // Broadcast message to all users in this conversation
      io.to(`conversation_${conversation_id}`).emit('new_message', {
        id: result.insertId,
        conversation_id: conversation_id,
        sender_id: sender_id,
        message: message,
        created_at: new Date().toISOString()
      });

      console.log(`✅ Message saved: "${message}" from user ${sender_id}`);
    });
  });

  // Handle typing indicator
  socket.on('typing', (data) => {
    const { conversation_id, user_id, typing } = data;
    socket.to(`conversation_${conversation_id}`).emit('user_typing', {
      user_id: user_id,
      typing: typing
    });
  });

  // User leaves chat
  socket.on('leave_chat', (data) => {
    const { user_id, conversation_id } = data;
    
    socket.leave(`conversation_${conversation_id}`);
    activeUsers.delete(user_id);

    socket.to(`conversation_${conversation_id}`).emit('user_offline', {
      user_id: user_id,
      status: 'offline'
    });

    console.log(`User ${user_id} left conversation ${conversation_id}`);
  });

  // Handle disconnect
  socket.on('disconnect', () => {
    console.log('User disconnected:', socket.id);
    
    // Find and remove user
    for (let [userId, userInfo] of activeUsers.entries()) {
      if (userInfo.socket_id === socket.id) {
        activeUsers.delete(userId);
        io.to(`conversation_${userInfo.conversation_id}`).emit('user_offline', {
          user_id: userId,
          status: 'offline'
        });
        break;
      }
    }
  });
});

const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
  console.log(`Chat server running on port ${PORT}`);
});
