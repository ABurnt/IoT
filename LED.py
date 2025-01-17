#!/usr/bin/python3
from gpiozero import PWMLED
import time
import os
import mail
import RPi.GPIO as GPIO
from mfrc522 import SimpleMFRC522
from mysql.connector import connect, Error

def read_RFID_and_control_led(value):
    reader = SimpleMFRC522()
    led = PWMLED(17)
    # Poziomy świecenia diody LED w zalezności od aktualnegj wartości natęzenia światła
    thresholds = {
        400: 0,
        300: 0.2,
        200: 0.4,
        100: 0.6,
        50: 0.8,
        0: 1.0
    }

    # Nawiązanie połączenia z bazą danych na początku
    try:
        
        connection = connect(
            host=os.environ["db_host"],
            user=os.environ["db_user"],
            password=os.environ["db_pass"],
            database=os.environ["db_name"],
        )

        if (0 < value < 10):
            # Nasłuchiwanie karty RFID przy wyjściu
            while True:
                id = reader.read_id_no_block()
                if id:
                    named_tuple = time.localtime()
                    time_string = time.strftime("%Y-%m-%d %H:%M:%S", named_tuple)
                    print("Do zobaczenia!")
                    led.value = 0
                    mail_query = """
                        SELECT notification.mail
                        FROM rfid
                        JOIN notification ON rfid.user_id = notification.user_id
                        WHERE rfid.card_id = %s
                    """
                    # Wysłanie informacji o wyjściu do bazy danych
                    departureDate = "INSERT INTO access_history (rfid_id, date, direction) VALUES (%s, %s, %s)"
                    # Wysłanie powiadomienia e-mail (jeśli włączone)
                    with connection.cursor() as cursor:
                        cursor.execute(mail_query, (id,))
                        mail_result = cursor.fetchone()[0]
                        if(mail_result == 1):
                            mail.send_notification(id, 'OUT', time_string)
                        cursor.execute(departureDate, (id, time_string, 'OUT'))
                        connection.commit()
                    break
                
                led.value = value / 10
        
        elif value == 0:
            # Nasłuchiwanie karty RFID przy wyjściu
            while True:
                id = reader.read_id_no_block()
                if id:
                    named_tuple = time.localtime()
                    time_string = time.strftime("%Y-%m-%d %H:%M:%S", named_tuple)
                    print("Do zobaczenia!")
                    led.value = 0
                    mail_query = """
                        SELECT notification.mail
                        FROM rfid
                        JOIN notification ON rfid.user_id = notification.user_id
                        WHERE rfid.card_id = %s
                    """
                    # Wysłanie informacji o wyjściu do bazy danych
                    departureDate = "INSERT INTO access_history (rfid_id, date, direction) VALUES (%s, %s, %s)"
                    # Wysłanie powiadomienia e-mail (jeśli włączone)
                    with connection.cursor() as cursor:
                        cursor.execute(mail_query, (id,))
                        mail_result = cursor.fetchone()[0]
                        if(mail_result == 1):
                            mail.send_notification(id, 'OUT', time_string)
                        cursor.execute(departureDate, (id, time_string, 'OUT'))
                        connection.commit()
                    break
                
                try:
                    var_query = "SELECT light_level FROM light WHERE id = %s"
                    
                    #Sprawdzenie stanu połączenia przed kazdym zapytaniem
                    if not connection.is_connected():
                        connection.reconnect()

                    time.sleep(3)
                    with connection.cursor() as cursor:
                        connection.commit()
                        cursor.execute(var_query, (1,))
                        var = cursor.fetchone()
                    
                    if not var:  # Sprawdzenie, czy wartość nie jest pusta
                        time.sleep(1)
                        continue
                    
                    var1 = int(var[0])
                    
                    # Minimalny próg zmiany
                    min_change_threshold = 0.05

                    for threshold, led_value in thresholds.items():
                        if var1 >= threshold:
                            # Sprawdź, czy nowa wartość LED różni się od aktualnej o więcej niż ustawiony próg
                            if abs(led.value - led_value) > min_change_threshold:
                                led.value = led_value
                                print(led_value)
                            break
                                
                except Error as e:
                    print(f"Błąd w trakcie wykonywania polecenia: {e}")

    except Error as e:
        print("Błąd połączenia: " + str(e))
    
    finally:
        # Zamykanie połączenia z bazą danych i czyszczenie GPIO
        try:
            if connection.is_connected():
                connection.close()
        except Exception as e:
            print(f"Błąd podczas zamykania połączenia z bazą danych: {e}")
        GPIO.cleanup()