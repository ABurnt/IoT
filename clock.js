function updateClock() {
    const now = new Date();
    const hours = now.getHours() % 12; // Godziny w formacie 12-godzinnym
    const minutes = now.getMinutes();
    const seconds = now.getSeconds();

    // Obliczanie kątów dla wskazówek
    const hourAngle = (hours * 30) + (minutes * (30 / 60)); // Każda godzina to 30 stopni
    const minuteAngle = (minutes * 6) + (seconds * (6 / 60)); // Każda minuta to 6 stopni
    const secondAngle = seconds * 6; // Każda sekunda to 6 stopni

    // Ustawianie kątów dla wskazówek
    document.querySelector('.hour-hand').style.transform = `rotate(${hourAngle}deg)`;
    document.querySelector('.minute-hand').style.transform = `rotate(${minuteAngle}deg)`;
    document.querySelector('.second-hand').style.transform = `rotate(${secondAngle}deg)`;
}

function updateDate() {
    const now = new Date();
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    const dateString = now.toLocaleDateString('pl-PL', options);
    const dateInfoElement = document.getElementById('date-info');
    if (dateInfoElement){
        dateInfoElement.innerText = `Dzisiaj jest ${dateString}`;
    }
}

window.onload = function() {
    updateClock();
    updateDate(); // Ustawienie daty przy ładowaniu strony
    setInterval(updateClock, 1000); // Ustawienie zegara przy ładowaniu strony
};