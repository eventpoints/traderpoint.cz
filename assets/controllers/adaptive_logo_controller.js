import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['logo', 'text', 'icon'];
    static values = {
        whiteLogo: String,
        coloredLogo: String
    };

    connect() {
        this.currentTheme = null; // Track current theme to avoid unnecessary updates
        this.updateLogoColor();

        // Use throttled scroll handler to reduce sensitivity
        this.throttleTimeout = null;
        this.boundHandleScroll = this.handleScroll.bind(this);

        window.addEventListener('scroll', this.boundHandleScroll);
        window.addEventListener('resize', this.boundHandleScroll);
    }

    disconnect() {
        window.removeEventListener('scroll', this.boundHandleScroll);
        window.removeEventListener('resize', this.boundHandleScroll);
        if (this.throttleTimeout) {
            clearTimeout(this.throttleTimeout);
        }
    }

    handleScroll() {
        // Throttle updates to reduce sensitivity
        if (this.throttleTimeout) {
            return;
        }

        this.throttleTimeout = setTimeout(() => {
            this.updateLogoColor();
            this.throttleTimeout = null;
        }, 50);
    }

    updateLogoColor() {
        if (!this.hasLogoTarget) {
            return;
        }

        // Get the logo's position
        const logoRect = this.logoTarget.getBoundingClientRect();
        const centerX = logoRect.left + logoRect.width / 2;
        const centerY = logoRect.top + logoRect.height / 2;

        // Temporarily hide navbar to detect background behind it
        const navbar = this.element;
        navbar.style.pointerEvents = 'none';
        const elementBehind = document.elementFromPoint(centerX, centerY);
        navbar.style.pointerEvents = '';

        if (!elementBehind) {
            this.setLightTheme();
            return;
        }

        // Walk up the DOM tree to find an element with a background color
        const bgColor = this.getBackgroundColor(elementBehind);

        // Determine if background is dark
        const isDark = this.isColorDark(bgColor);

        // Only update if theme has changed
        const newTheme = isDark ? 'dark' : 'light';
        if (this.currentTheme === newTheme) {
            return;
        }

        this.currentTheme = newTheme;

        if (isDark) {
            this.setDarkTheme();
        } else {
            this.setLightTheme();
        }
    }

    getBackgroundColor(element) {
        // Walk up the DOM tree to find the first element with a solid background
        let current = element;
        let maxDepth = 10; // Prevent infinite loop
        let depth = 0;

        while (current && depth < maxDepth) {
            const bgColor = window.getComputedStyle(current).backgroundColor;

            // Check if background color is not transparent
            if (bgColor && bgColor !== 'rgba(0, 0, 0, 0)' && bgColor !== 'transparent') {
                return bgColor;
            }

            current = current.parentElement;
            depth++;
        }

        // Default to white if no background found
        return 'rgb(255, 255, 255)';
    }

    isColorDark(color) {
        // Parse RGB values from color string
        const rgb = color.match(/\d+/g);
        if (!rgb || rgb.length < 3) {
            return false; // Default to light if we can't parse
        }

        const [r, g, b] = rgb.map(Number);

        // Calculate relative luminance using WCAG formula
        const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

        // If luminance is less than 0.5, it's a dark background
        return luminance < 0.5;
    }

    setDarkTheme() {
        // Dark background -> white logo and text
        if (this.hasLogoTarget) {
            // Fade out, change src, fade in
            this.logoTarget.style.opacity = '0';
            setTimeout(() => {
                this.logoTarget.src = this.whiteLogoValue;
                this.logoTarget.style.opacity = '1';
            }, 150);
        }

        this.textTargets.forEach(el => {
            el.classList.remove('text-primary', 'border-primary');
            el.classList.add('text-white', 'border-white');
        });

        this.iconTargets.forEach(el => {
            el.classList.remove('text-primary');
            el.classList.add('text-white');
        });
    }

    setLightTheme() {
        // Light background -> colored logo and black text
        if (this.hasLogoTarget) {
            // Fade out, change src, fade in
            this.logoTarget.style.opacity = '0';
            setTimeout(() => {
                this.logoTarget.src = this.coloredLogoValue;
                this.logoTarget.style.opacity = '1';
            }, 150);
        }

        this.textTargets.forEach(el => {
            el.classList.remove('text-white', 'border-white');
            el.classList.add('text-dark', 'border-dark');
        });

        this.iconTargets.forEach(el => {
            el.classList.remove('text-white');
            el.classList.add('text-dark');
        });
    }
}
