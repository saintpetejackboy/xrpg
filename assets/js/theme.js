// /assets/theme.js

function getThemeSetting() {
    return localStorage.getItem('theme') || 'light';
}

function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
}

function setAccentColors(light, dark) {
    document.documentElement.style.setProperty('--theme-accent', light);
    document.documentElement.style.setProperty('--theme-accent2', dark);
    localStorage.setItem('accent_light', light);
    localStorage.setItem('accent_dark', dark);
}

function setRadius(val) {
    document.documentElement.setAttribute('data-radius', val);
    localStorage.setItem('radius', val);
}

function setShadow(val) {
    document.documentElement.setAttribute('data-shadow-blur', val);
    localStorage.setItem('shadow_blur', val);
}

function initThemeFromPrefs() {
    let theme = getThemeSetting();
    setTheme(theme);

    let accentLight = localStorage.getItem('accent_light') || '#558cff';
    let accentDark = localStorage.getItem('accent_dark') || '#558cff';
    setAccentColors(accentLight, accentDark);

    let radius = localStorage.getItem('radius') || '18';
    setRadius(radius);
    let shadow = localStorage.getItem('shadow_blur') || '0.12';
    setShadow(shadow);

    // Set UI controls if present
    let accLightInp = document.getElementById('accent-picker-light');
    if (accLightInp) accLightInp.value = accentLight;
    let accDarkInp = document.getElementById('accent-picker-dark');
    if (accDarkInp) accDarkInp.value = accentDark;
    let themeToggle = document.getElementById('theme-toggle-btn');
    if (themeToggle) themeToggle.innerText = (theme === 'dark' ? 'Light Theme' : 'Dark Theme');
    let radiusInp = document.getElementById('roundness-range');
    if (radiusInp) radiusInp.value = radius;
    let shadowInp = document.getElementById('shadow-range');
    if (shadowInp) shadowInp.value = shadow;
}

document.addEventListener('DOMContentLoaded', function() {
    initThemeFromPrefs();

    document.getElementById('theme-toggle-btn')?.addEventListener('click', function() {
        let theme = (getThemeSetting() === 'dark') ? 'light' : 'dark';
        setTheme(theme);
        this.innerText = (theme === 'dark' ? 'Light Theme' : 'Dark Theme');
    });
    document.getElementById('accent-picker-light')?.addEventListener('input', function() {
        setAccentColors(this.value, document.getElementById('accent-picker-dark').value);
    });
    document.getElementById('accent-picker-dark')?.addEventListener('input', function() {
        setAccentColors(document.getElementById('accent-picker-light').value, this.value);
    });
    document.getElementById('roundness-range')?.addEventListener('input', function() {
        setRadius(this.value);
    });
    document.getElementById('shadow-range')?.addEventListener('input', function() {
        setShadow(this.value);
    });
});
