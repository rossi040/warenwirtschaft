// Funktion zur Aktualisierung der UTC-Zeit im gewünschten Format
function updateHeaderTime() {
    const now = new Date();
    
    // UTC Zeit formatieren
    const year = now.getUTCFullYear();
    const month = String(now.getUTCMonth() + 1).padStart(2, '0');
    const day = String(now.getUTCDate()).padStart(2, '0');
    const hours = String(now.getUTCHours()).padStart(2, '0');
    const minutes = String(now.getUTCMinutes()).padStart(2, '0');
    const seconds = String(now.getUTCSeconds()).padStart(2, '0');
    
    // Formatierte Zeit zusammensetzen
    const formattedTime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    
    // Zeit im Header aktualisieren
    document.getElementById('current-time').textContent = formattedTime;
}

// Zeit initial setzen und alle Sekunde aktualisieren
updateHeaderTime();
setInterval(updateHeaderTime, 1000);