// assets/controllers/consent_controller.js
import { Controller } from '@hotwired/stimulus'
import { Offcanvas, Modal } from 'bootstrap'

export default class extends Controller {
    static targets = ['pref', 'stat', 'mkt'] // checkboxes
    static values = {
        cookie: { type: String, default: 'tp_consent' },
        version: { type: Number, default: 1 },
        vendors: Array
    }

    connect() {
        // Prepare BS instances
        this.offcanvas = Offcanvas.getOrCreateInstance(this.element)
        // Optional: a separate Modal for “details” (if present)
        const modalEl = document.querySelector('[data-consent-modal]')
        this.modal = modalEl ? Modal.getOrCreateInstance(modalEl) : null

        // Check existing consent
        const current = this.readCookie()
        if (!current) {
            // First visit → show offcanvas (backdrop already disabled in markup)
            this.open()
        } else {
            // Apply saved choices on load
            this.applyToUI(current.choices)
            this.updateConsentMode(current.choices)
            this.enableDeferredAssets(current.choices)
        }
    }

    // UI actions
    open() { this.offcanvas?.show() }
    close() { this.offcanvas?.hide() }

    openModal(event) {
        event?.preventDefault()
        this.modal?.show()
    }
    closeModal(event) {
        event?.preventDefault()
        this.modal?.hide()
    }

    // Buttons
    acceptAll(event) {
        event?.preventDefault()
        const choices = { preferences: true, statistics: true, marketing: true }
        this.persistAndApply(choices)
    }

    rejectAll(event) {
        event?.preventDefault()
        const choices = { preferences: false, statistics: false, marketing: false }
        this.persistAndApply(choices, { purge: true })
    }

    save(event) {
        event?.preventDefault()
        const choices = this.choicesFromUI()
        this.persistAndApply(choices, { purge: (!choices.statistics || !choices.marketing) })
    }

    // Helpers
    persistAndApply(choices, opts = {}) {
        this.writeCookie({ v: this.versionValue, ts: Math.floor(Date.now()/1000), choices })
        this.updateConsentMode(choices)
        this.enableDeferredAssets(choices)
        if (opts.purge) this.purgeThirdPartyCookies()
        this.close()
        this.closeModal()
    }

    choicesFromUI() {
        return {
            preferences: !!this.prefTarget?.checked,
            statistics:  !!this.statTarget?.checked,
            marketing:   !!this.mktTarget?.checked
        }
    }

    applyToUI(choices = {}) {
        if (this.hasPrefTarget) this.prefTarget.checked = !!choices.preferences
        if (this.hasStatTarget) this.statTarget.checked = !!choices.statistics
        if (this.hasMktTarget)  this.mktTarget.checked  = !!choices.marketing
    }

    updateConsentMode(choices = {}) {
        const g = (typeof window.gtag === 'function') ? window.gtag : () => {}
        g('consent', 'update', {
            analytics_storage: choices.statistics ? 'granted' : 'denied',
            functionality_storage: choices.preferences ? 'granted' : 'denied',
            ad_storage: choices.marketing ? 'granted' : 'denied',
            ad_user_data: choices.marketing ? 'granted' : 'denied',
            ad_personalization: choices.marketing ? 'granted' : 'denied'
        })
    }

    enableDeferredAssets(choices = {}) {
        const ok = new Set(['necessary'])
        if (choices.preferences) ok.add('preferences')
        if (choices.statistics)  ok.add('statistics')
        if (choices.marketing)   ok.add('marketing')

        // <script type="text/plain" data-consent="statistics marketing" src=...>
        document.querySelectorAll('script[type="text/plain"][data-consent]').forEach(s => {
            const needed = s.getAttribute('data-consent').split(/\s+/).filter(Boolean)
            if (needed.every(n => ok.has(n))) {
                const n = document.createElement('script')
                for (const { name, value } of [...s.attributes]) {
                    if (name !== 'type') n.setAttribute(name, value)
                }
                n.type = 'text/javascript'
                if (s.text && !s.src) n.text = s.text
                s.replaceWith(n)
            }
        })

        // Iframe placeholders:
        // <div data-consent-iframe="marketing" data-src="https://www.youtube-nocookie.com/embed/ID"></div>
        document.querySelectorAll('[data-consent-iframe]').forEach(div => {
            if (div.dataset.loaded) return
            const needed = (div.getAttribute('data-consent-iframe') || '').split(/\s+/).filter(Boolean)
            if (needed.every(n => ok.has(n))) {
                const src = div.dataset.src
                if (src) {
                    const iframe = document.createElement('iframe')
                    iframe.src = src
                    if (div.dataset.allow) iframe.setAttribute('allow', div.dataset.allow)
                    iframe.className = div.className
                    div.replaceWith(iframe)
                }
                div.dataset.loaded = '1'
            }
        })
    }

    // Cookie utils
    readCookie() {
        const m = document.cookie.match(new RegExp('(?:^|; )' + this.cookieName() + '=([^;]*)'))
        if (!m) return null
        try { return JSON.parse(decodeURIComponent(m[1])) } catch { return null }
    }
    writeCookie(payload) {
        const ONE_YEAR = 60 * 60 * 24 * 365
        document.cookie = `${this.cookieName()}=${encodeURIComponent(JSON.stringify(payload))}; Path=/; Max-Age=${ONE_YEAR}; SameSite=Lax; Secure`
    }
    cookieName() { return this.cookieValue || this.cookieValue === '' ? this.cookieValue : this.cookieValue = this.cookieValue || this.cookieValueDefault() }
    cookieValueDefault() { return this.hasCookieValue ? this.cookieValue : 'tp_consent' }

    // Optional cleanup when withdrawing consent
    purgeThirdPartyCookies() {
        if (!this.hasVendorsValue) {
            return
        }

        const all = document.cookie
            .split(';')
            .map(s => s.trim().split('=')[0])

        this.vendorsValue.forEach(vendor => {
            (vendor.cookies || []).forEach(pattern => {
                const re = new RegExp('^' + pattern.replace(/\*/g, '.*') + '$')
                all
                    .filter(name => re.test(name))
                    .forEach(name => {
                        document.cookie = `${name}=; Max-Age=0; Path=/; SameSite=Lax; Secure`
                    })
            })
        })
    }

}
