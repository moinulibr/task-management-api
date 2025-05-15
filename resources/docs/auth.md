# Authentication

APIs for user registration, login, and logout.

## Register user

Registers a new user.

@response 201 {
  "status": "success",
  "message": "User registered successfully.",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "eyJ0eXAiOiJKV..."
  }
}
