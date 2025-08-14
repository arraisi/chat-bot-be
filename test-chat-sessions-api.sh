#!/bin/bash

echo "Testing Chat Session API endpoints..."
echo "====================================="

echo ""
echo "1. Testing GET /api/chat-sessions (get all sessions):"
curl -s http://localhost:8000/api/chat-sessions | jq '.' || echo "Request failed"

echo ""
echo "2. Creating a new chat session:"
curl -s -X POST http://localhost:8000/api/chat-sessions \
    -H "Content-Type: application/json" \
    -d '{
    "session_id": "test-session-456",
    "title": "API Test Session",
    "authority": "ADMIN"
  }' | jq '.' || echo "Request failed"

echo ""
echo "3. Sending a message to the session:"
curl -s -X POST http://localhost:8000/api/chat-sessions/test-session-456/messages \
    -H "Content-Type: application/json" \
    -d '{
    "content": "What is the vision and mission of Peruri?",
    "category": "code-of-conduct",
    "authority": "ADMIN",
    "message_id": "test-msg-001"
  }' | jq '.' || echo "Request failed"

echo ""
echo "4. Getting the session with messages:"
curl -s http://localhost:8000/api/chat-sessions/test-session-456 | jq '.' || echo "Request failed"

echo ""
echo "5. Getting all sessions again:"
curl -s http://localhost:8000/api/chat-sessions | jq '.' || echo "Request failed"

echo ""
echo "Test completed!"
