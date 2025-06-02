# Google Calendar Integration API

This document describes the API endpoints used for connecting a user's Google Calendar to the application.

## Authentication

These endpoints generally require the user to be authenticated via Sanctum for the application to know which user's Google Account is being linked.

## OAuth 2.0 Flow

The integration uses Google's OAuth 2.0 protocol. The flow is as follows:

1.  The user initiates the connection from the frontend application.
2.  The frontend calls the `/api/google/calendar/auth` endpoint.
3.  The backend redirects the user to Google's OAuth consent screen.
4.  The user grants permission on the Google consent screen.
5.  Google redirects the user back to the `/api/google/calendar/callback` endpoint (specified as the redirect URI in Google Cloud Console).
6.  The backend exchanges the authorization code (received in the callback) for an access token and a refresh token.
7.  These tokens are securely stored for the user, allowing the application to interact with their Google Calendar on their behalf.
8.  The backend redirects the user back to a frontend page, indicating success or failure.

## Endpoints

### 1. Initiate Google Calendar Authorization

*   **Method:** `GET`
*   **URL:** `/api/google/calendar/auth`
*   **Description:** Redirects the authenticated user to Google's OAuth consent screen to grant the application permission to access their calendar.
*   **Required Authentication:** Yes (Sanctum)
*   **Request Parameters:** None
*   **Example Request (from a browser or frontend):**
    ```
    GET /api/google/calendar/auth
    Authorization: Bearer <YOUR_API_TOKEN>
    ```
    (Typically, the frontend would make this request after the user clicks a "Connect Google Calendar" button, and the browser would follow the redirect.)
*   **Successful Response:**
    *   `302 Found` (Redirect): Redirects to Google's OAuth consent page. The redirect URL will look something like:
      `https://accounts.google.com/o/oauth2/v2/auth?response_type=code&access_type=offline&client_id=<YOUR_GOOGLE_CLIENT_ID>&redirect_uri=<YOUR_REDIRECT_URI>&state=<STATE_STRING>&scope=<SCOPES>&prompt=consent`
*   **Error Responses:**
    *   `401 Unauthorized`: If the user is not authenticated with the application.
    *   Errors if Google Client ID/Secret/Redirect URI are not configured on the server (server-side error, may result in a 500 or redirect to an error page).

### 2. Handle Google Calendar Callback

*   **Method:** `GET`
*   **URL:** `/api/google/calendar/callback`
*   **Description:** Handles the callback from Google after the user has authorized (or denied) the application. It exchanges the authorization code for an access token and refresh token, then stores these for the user.
*   **Required Authentication:** Yes (Sanctum - the session cookie from the initial `/auth` redirect helps maintain the user's session if redirects are handled by the same agent e.g. browser. If the callback is to a pure API backend, the user's identity might need to be re-established or passed via the `state` parameter securely).
    *Note: The `GoogleCalendarController` in this project relies on the existing Sanctum authentication of the user who initiated the flow.*
*   **Query Parameters (from Google):**
    *   `code` (string): The authorization code, if the user granted access.
    *   `error` (string, optional): An error code if the user denied access or an error occurred (e.g., `access_denied`).
    *   `state` (string, optional): The state parameter originally sent by the application (should be verified if used).
*   **Example Request (from Google to your redirect URI):**
    ```
    GET /api/google/calendar/callback?code=4/0AeaYSHAmpleAuthCode&scope=https://www.googleapis.com/auth/calendar...
    ```
*   **Successful Response:**
    *   `302 Found` (Redirect): Redirects the user back to a pre-configured frontend URL (e.g., `env('FRONTEND_URL')/settings?google_auth_success=true`).
*   **Error Response (Redirect):**
    *   `302 Found` (Redirect): Redirects the user back to a pre-configured frontend URL with an error query parameter (e.g., `env('FRONTEND_URL')/settings?google_auth_error=token_fetch_failed`).
    *   Specific error query parameters might include: `missing_code`, `token_fetch_failed`, or the error code from Google (e.g., `access_denied`).

## Post-Connection

Once the user's Google Calendar is connected:

*   Events created or updated in this application will be automatically synced (created or updated) to their primary Google Calendar.
*   Events deleted in this application will be automatically deleted from their Google Calendar.
*   The sync is primarily one-way from this application to Google Calendar for events managed here.

See the User Guide for instructions on how to connect your calendar.
