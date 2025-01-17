#!/usr/bin/env python

import RPi.GPIO as GPIO
from mfrc522 import SimpleMFRC522
from mysql.connector import connect, Error
import time
import os
import mail

# Oczekiwanie na przyłozenie karty RFID podczas wejścia
def read_RFID():
    reader = SimpleMFRC522()
    print("Proszę, przyłóż kartę RFID.")
    try:
        card_id = reader.read_id()
        print("Twój ID karty to: " + str(card_id))
        return card_id
    finally:
        GPIO.cleanup()

# Funkcja połączenia z bazą danych
def db_connect(card_id):
    try:
        print("Trwa łączenie z bazą danych...")
        with connect(
            host=os.environ["db_host"],
            user=os.environ["db_user"],
            password=os.environ["db_pass"],
            database=os.environ["db_name"],
        ) as connection:
            print("Połączenie udane.")
            print("Sprawdzanie karty w systemie...")

            check_if_card_exists = "SELECT COUNT(card_id) FROM rfid WHERE card_id=%s"
            with connection.cursor() as cursor:
                cursor.execute(check_if_card_exists, (card_id,))
                count = cursor.fetchone()[0]

                if count == 0:
                    print("Karta nie została odnaleziona w systemie.")
                    return -1
                
                print("Logowanie pomyślne.")
                entry_time = time.strftime("%Y-%m-%d %H:%M:%S", time.localtime())
                mail_query = """
                    SELECT notification.mail
                    FROM rfid
                    JOIN notification ON rfid.user_id = notification.user_id
                    WHERE rfid.card_id = %s
                """
                cursor.execute(mail_query, (card_id,))
                mail_result = cursor.fetchone()[0]
                # Wysłanie powiadomienia e-mail (jeśli włączone)
                if(mail_result == 1):
                    mail.send_notification(card_id, 'IN', entry_time)
                # Wysłanie informacji o wejściu do bazy danych
                insert_access_incident = "INSERT INTO access_history (rfid_id, date, direction) VALUES (%s, %s, %s)"
                cursor.execute(insert_access_incident, (card_id, entry_time, 'IN'))
                connection.commit()
                check_light_mode = """
                    SELECT users.light_mode, users.name, users.lastname, users.login 
                    FROM users 
                    JOIN rfid ON users.id = rfid.user_id 
                    WHERE rfid.card_id = %s
                """
                cursor.execute(check_light_mode, (card_id,))
                
                light_mode, user_name = cursor.fetchone()[:2]
                
                if light_mode == 0:
                    print(f"Poziom światła dla użytkownika {user_name} to: tryb automatyczny")
                else:
                    print(f"Poziom światła dla użytkownika {user_name} to: {light_mode} - tryb manualny")
                
                return int(light_mode)

    except Error as e:
        print("Błąd połączenia: " + str(e))
        return None

def main():
    print("System IoT do symulacji automatyzacji i zarządzania inteligentnymi pomieszczeniami.")
    rfid_id = read_RFID()
    connection_result = db_connect(rfid_id)
    return connection_result

if __name__ == "__main__":
    main()
