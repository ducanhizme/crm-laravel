# User Guide: Connecting Your Google Calendar

Connecting your Google Calendar to our application allows you to seamlessly sync events created here to your personal Google Calendar. This helps you keep all your events in one place!

## How it Works

When you connect your Google Calendar:

*   **New Events:** Events you create in our application will be automatically added to your primary Google Calendar.
*   **Event Updates:** If you update an event in our application (e.g., change the time, title, or description), the corresponding event in your Google Calendar will also be updated.
*   **Event Deletions:** Deleting an event in our application will also remove it from your Google Calendar.

Currently, the synchronization is **one-way**. This means events are synced from our application to your Google Calendar. Changes made directly in Google Calendar to events created by our application might not be reflected back here.

## Steps to Connect Your Google Calendar

1.  **Navigate to Settings:** Log in to your account and go to your account settings or profile page. Look for a section related to "Integrations" or "Connected Apps."

2.  **Find Google Calendar:** You should see an option for "Google Calendar." Click the "Connect" or "Link Google Calendar" button.

3.  **Redirect to Google:** You will be redirected to Google's secure sign-in page.
    *   If you are not already logged into your Google account, you will be asked to log in.
    *   If you have multiple Google accounts, choose the account whose calendar you wish to connect.

4.  **Grant Permissions (Google Consent Screen):**
    *   Google will show you a consent screen asking for permission for our application to:
        *   **View your calendars** (`https://www.googleapis.com/auth/calendar`)
        *   **View and edit events on all your calendars** (`https://www.googleapis.com/auth/calendar.events`)
    *   Our application needs these permissions to create, update, and delete events on your behalf. We will only interact with events that are created through our application or explicitly linked.
    *   Review the permissions and click **"Allow"** or **"Grant"**.

5.  **Redirection and Confirmation:**
    *   After you grant permission, Google will redirect you back to our application.
    *   You should see a confirmation message indicating that your Google Calendar has been successfully connected. This might be in your settings page or as a notification.

## What to Expect After Connecting

*   Any new events you create within our application (that are eligible for syncing) will now automatically appear in your primary Google Calendar.
*   Updates or deletions to these events within our application will also be reflected in your Google Calendar.
*   The `google_calendar_event_id` field for an event (visible via API or potentially in advanced views) will be populated once it's synced.

## Troubleshooting & Disconnecting

*   **Connection Issues:** If you encounter issues during the connection process (e.g., error messages), please try again. Ensure you are logged into the correct Google account and grant the requested permissions. If problems persist, contact our support.
*   **Disconnecting:** If you wish to disconnect your Google Calendar, you should find an option in the same "Integrations" or "Connected Apps" section of your settings to "Disconnect" or "Unlink" your Google Calendar. This will stop any future syncing of events. You can also revoke access directly from your Google Account security settings (look for "Third-party apps with account access").

By following these steps, you can easily integrate your Google Calendar and keep your schedule harmonized!
