# Events API

This document describes the API endpoints for managing events within the application.

## Authentication

All event endpoints require authentication via Sanctum. Ensure your requests include a valid API token in the `Authorization` header:

`Authorization: Bearer <YOUR_API_TOKEN>`

## Endpoints

### 1. List Events

*   **Method:** `GET`
*   **URL:** `/api/events`
*   **Description:** Retrieves a paginated list of events accessible to the authenticated user, filtered by their workspaces.
*   **Query Parameters:**
    *   `filter[user_id]` (integer, optional): Filter events by the ID of the user who created them.
    *   `filter[workspace_id]` (integer, optional): Filter events by workspace ID. User must have access to this workspace.
    *   `filter[event_type]` (string, optional): Filter by event type (e.g., `meeting`, `task`, `reminder`).
    *   `filter[starts_between]` (string, optional): Filter events starting within a date range. Format: `YYYY-MM-DD HH:MM:SS,YYYY-MM-DD HH:MM:SS`. Example: `2024-01-01 00:00:00,2024-01-31 23:59:59`.
    *   `filter[ends_between]` (string, optional): Filter events ending within a date range. Format: `YYYY-MM-DD HH:MM:SS,YYYY-MM-DD HH:MM:SS`.
    *   `sort` (string, optional): Sort results. Allowed fields: `start_time`, `end_time`, `title`, `created_at`. Prepend `-` for descending order (e.g., `-start_time`).
    *   `per_page` (integer, optional): Number of events per page. Default: 15.
    *   `page` (integer, optional): Page number for pagination.
*   **Example Request:**
    ```bash
    curl -X GET "/api/events?filter[event_type]=meeting&sort=-start_time" \
      -H "Authorization: Bearer <YOUR_API_TOKEN>" \
      -H "Accept: application/json"
    ```
*   **Example Successful Response (200 OK):**
    ```json
    {
        "data": [
            {
                "id": 1,
                "title": "Team Meeting",
                "description": "Discuss project updates.",
                "event_type": "meeting",
                "start_time": "2024-06-15 10:00:00",
                "end_time": "2024-06-15 11:00:00",
                "user_id": 1,
                "workspace_id": 1,
                "google_calendar_event_id": "gcal_event_id_123",
                "created_at": "2024-06-01 10:00:00",
                "updated_at": "2024-06-01T10:00:00"
            }
            // ... more events
        ],
        "links": {
            // ... pagination links
        },
        "meta": {
            // ... pagination meta
        }
    }
    ```
*   **Error Responses:**
    *   `401 Unauthorized`: If authentication fails.

### 2. Create Event

*   **Method:** `POST`
*   **URL:** `/api/events`
*   **Description:** Creates a new event. If the user has connected their Google Calendar, the event will also be created there.
*   **Request Body (JSON):**
    *   `title` (string, required): Title of the event. Max 255 characters.
    *   `description` (string, optional): Description of the event.
    *   `event_type` (string, required): Type of event (e.g., `meeting`, `task`). Max 100 characters.
    *   `start_time` (datetime, required): Start date and time (e.g., `YYYY-MM-DD HH:MM:SS`).
    *   `end_time` (datetime, required): End date and time. Must be after or equal to `start_time`.
    *   `workspace_id` (integer, required): ID of the workspace this event belongs to. The authenticated user must be a member of this workspace.
    *   `google_calendar_event_id` (string, optional): This field is typically not sent by the client on creation but is listed here for completeness as part of the model. It's populated by the server if synced to Google Calendar.
*   **Example Request:**
    ```bash
    curl -X POST "/api/events" \
      -H "Authorization: Bearer <YOUR_API_TOKEN>" \
      -H "Content-Type: application/json" \
      -H "Accept: application/json" \
      -d '{
            "title": "Client Call",
            "description": "Follow up with Client X.",
            "event_type": "call",
            "start_time": "2024-06-16 14:00:00",
            "end_time": "2024-06-16 14:30:00",
            "workspace_id": 1
          }'
    ```
*   **Example Successful Response (201 Created):**
    ```json
    {
        "id": 2,
        "title": "Client Call",
        "description": "Follow up with Client X.",
        "event_type": "call",
        "start_time": "2024-06-16 14:00:00",
        "end_time": "2024-06-16 14:30:00",
        "user_id": 1,
        "workspace_id": 1,
        "google_calendar_event_id": "new_gcal_event_id_456", // If synced
        "created_at": "2024-06-01 11:00:00",
        "updated_at": "2024-06-01 11:00:00"
    }
    ```
*   **Error Responses:**
    *   `401 Unauthorized`: Authentication failure.
    *   `422 Unprocessable Entity`: Validation errors (e.g., missing fields, invalid `workspace_id`, date format issues).
      ```json
      {
          "message": "The given data was invalid.",
          "errors": {
              "title": ["The title field is required."],
              "workspace_id": ["The selected workspace is invalid or you do not have access to it."]
          }
      }
      ```

### 3. Show Event

*   **Method:** `GET`
*   **URL:** `/api/events/{id}`
*   **Description:** Retrieves details for a single event.
*   **Path Parameters:**
    *   `id` (integer, required): The ID of the event.
*   **Example Request:**
    ```bash
    curl -X GET "/api/events/2" \
      -H "Authorization: Bearer <YOUR_API_TOKEN>" \
      -H "Accept: application/json"
    ```
*   **Example Successful Response (200 OK):**
    ```json
    {
        "id": 2,
        "title": "Client Call",
        // ... other fields ...
    }
    ```
*   **Error Responses:**
    *   `401 Unauthorized`: Authentication failure.
    *   `403 Forbidden`: User does not have access to the workspace the event belongs to.
    *   `404 Not Found`: Event with the given ID not found.

### 4. Update Event

*   **Method:** `PUT` or `PATCH`
*   **URL:** `/api/events/{id}`
*   **Description:** Updates an existing event. If the event is synced with Google Calendar, the corresponding Google Calendar event will also be updated. If it wasn't synced but the user is, it might get created on Google Calendar.
*   **Path Parameters:**
    *   `id` (integer, required): The ID of the event to update.
*   **Request Body (JSON):** (Similar to Create Event, all fields optional during update)
    *   `title` (string, optional): Title of the event.
    *   `description` (string, optional): Description.
    *   `event_type` (string, optional): Type of event.
    *   `start_time` (datetime, optional): Start date and time.
    *   `end_time` (datetime, optional): End date and time.
    *   `workspace_id` (integer, optional): Workspace ID. If provided, user must have access.
*   **Example Request:**
    ```bash
    curl -X PUT "/api/events/2" \
      -H "Authorization: Bearer <YOUR_API_TOKEN>" \
      -H "Content-Type: application/json" \
      -H "Accept: application/json" \
      -d '{
            "title": "Client Call (Rescheduled)",
            "start_time": "2024-06-17 10:00:00",
            "end_time": "2024-06-17 10:30:00"
          }'
    ```
*   **Example Successful Response (200 OK):**
    ```json
    {
        "id": 2,
        "title": "Client Call (Rescheduled)",
        "start_time": "2024-06-17 10:00:00",
        "end_time": "2024-06-17 10:30:00",
        // ... other fields ...
    }
    ```
*   **Error Responses:**
    *   `401 Unauthorized`.
    *   `403 Forbidden`: User cannot access/modify the event.
    *   `404 Not Found`: Event not found.
    *   `422 Unprocessable Entity`: Validation errors.

### 5. Delete Event

*   **Method:** `DELETE`
*   **URL:** `/api/events/{id}`
*   **Description:** Deletes an event. If synced with Google Calendar, the corresponding Google Calendar event will also be deleted.
*   **Path Parameters:**
    *   `id` (integer, required): The ID of the event to delete.
*   **Example Request:**
    ```bash
    curl -X DELETE "/api/events/2" \
      -H "Authorization: Bearer <YOUR_API_TOKEN>" \
      -H "Accept: application/json"
    ```
*   **Example Successful Response (204 No Content):** (Empty response body)
*   **Error Responses:**
    *   `401 Unauthorized`.
    *   `403 Forbidden`: User cannot delete the event.
    *   `404 Not Found`: Event not found.
