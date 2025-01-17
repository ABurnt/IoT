#!/usr/bin/python

import smbus
import time
import os
from mysql.connector import connect, Error

DEVICE = 0x23

# Komendy dla czujnika BH1750
POWER_DOWN = 0x00
POWER_ON = 0x01
RESET = 0x07

ONE_TIME_HIGH_RES_MODE_1 = 0x20

bus = smbus.SMBus(1)

# Globalna zmienna do przechowywania ostatniego poziomu światła
last_light_level = None

def convertToNumber(data):
    """Konwertuje dane z czujnika na wartość natężenia światła."""
    result = (data[1] + (256 * data[0])) / 1.2
    return result

def readLight(addr=DEVICE):
    """Odczytuje natężenie światła z czujnika."""
    try:
        data = bus.read_i2c_block_data(addr, ONE_TIME_HIGH_RES_MODE_1)
        return convertToNumber(data)
    except Exception as e:
        print(f"Błąd podczas odczytu danych: {e}")
        return None
    
def writeLightLevelToDatabase(cursor, connection, lightLevel):
    if lightLevel is not None:
        try:
            currentLightLevel = "UPDATE light SET light_level = %s WHERE id = %s"
            cursor.execute(currentLightLevel, (int(lightLevel), 1))
            connection.commit()
            print(str(int(lightLevel)))
        except Error as e:
            print(f"Błąd podczas zapisu do bazy danych: {e}")

def main(interval=3):
    """Główna funkcja programu."""
    global last_light_level
    
    # Funkcja połączenia z bazą danych
    try:
        connection = connect(
            host=os.environ["db_host"],
            user=os.environ["db_user"],
            password=os.environ["db_pass"],
            database=os.environ["db_name"],
        )
        cursor = connection.cursor()

        while True:
            lightLevel = readLight()
            
            # Weryfikacja, czy wartość natężenia światła zmieniła się o więcej niż 5 jednostek
            if lightLevel is not None:
                if last_light_level is None or abs(lightLevel - last_light_level) > 5:
                    writeLightLevelToDatabase(cursor, connection, lightLevel)
                    last_light_level = lightLevel
            
            time.sleep(interval)

    except Error as e:
        print(f"Błąd podczas łączenia z bazą danych: {e}")
    finally:
        if 'connection' in locals() and connection.is_connected():
            cursor.close()
            connection.close()

if __name__ == "__main__":
    main()
