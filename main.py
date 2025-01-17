#!/usr/bin/env python

import Auth
import LED
# Główna funkcja programu wywołująca pozostałe podprogramy Auth oraz LED
def main():
    try:
        authorization_process = Auth.main()
        
        if authorization_process == -1:
            print("Weryfikacja zakończona niepomyślnie.")
        else:
            print("Weryfikacja zakończona pomyślnie.")
            value = authorization_process if authorization_process != 0 else 0
            LED.read_RFID_and_control_led(value)
    except (ValueError, ImportError) as e:
        print(f"Błąd podczas uwierzytelniania: {e}")

if __name__ == "__main__":
    main()