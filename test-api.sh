#!/bin/bash

echo "Testing Chat Session API..."

# Test simple GET
echo "1. Testing GET /api/chat-sessions"
curl -s -X GET 'http://localhost:8000/api/chat-sessions' -H 'Accept: application/json' | jq '.' || echo "Failed"

echo ""
echo "2. Testing POST /api/chat-sessions/session_123/messages"

# Test send message
curl -s -X POST 'http://localhost:8000/api/chat-sessions/session_123/messages' \
    -H 'Content-Type: application/json' \
    -H 'Accept: application/json' \
    -d '{
    "content": "Halo, saya ingin bertanya tentang kebijakan SDM",
    "category": "question",
    "authority": "SDM",
    "message_id": "msg_123"
  }' | jq '.' || echo "Failed"

echo ""
echo "3. Testing GET specific session"
curl -s -X GET 'http://localhost:8000/api/chat-sessions/session_123' -H 'Accept: application/json' | jq '.' || echo "Failed"
