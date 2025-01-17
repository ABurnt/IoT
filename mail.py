#!/usr/bin/env python

# Wykorzystanie zewnętrznych bibliotek producenta Twilio
import os
from sendgrid import SendGridAPIClient
from sendgrid.helpers.mail import Mail

def send_notification(RFID_ID, DIRECTION, DATE):
    # Sprawdzenie kierunku i przygotowanie treści wiadomości
    if DIRECTION == "IN":
        content = f"Powiadomienie: nastąpiło wejście do pomieszczenia. Szczegóły: ID karty - {RFID_ID}, data: {DATE}"
    elif DIRECTION == "OUT":
        content = f"Powiadomienie: nastąpiło wyjście z pomieszczenia. Szczegóły: ID karty - {RFID_ID}, data: {DATE}"

    message = Mail(
        from_email=os.environ["from_email"],
        to_emails=os.environ["to_email"],
        subject='Powiadomienie o dostępie',
        html_content=f'<strong>{content}</strong>'
    )
    
    # Funkcja wysyłania wiadomości e-mail przez API
    try:
        sg = SendGridAPIClient(os.environ.get('SENDGRID_API_KEY'))
        response = sg.send(message)
        print(response.status_code)
        print(response.body)
        print(response.headers)
    except Exception as e:
        print(str(e))
