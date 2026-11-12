

/* ================================
   JCCS Website Main JavaScript
   ================================ */

document.addEventListener("DOMContentLoaded", () => {
    setupMobileMenu();
    setupLanguageSwitcher();
    setupServicePopups();
    setupQuoteForm();
});

/* ================================
   Mobile Navigation
   ================================ */

function setupMobileMenu() {
    const menuToggle = document.getElementById("menu-toggle");
    const navMenu = document.getElementById("nav-menu");

    if (!menuToggle || !navMenu) return;

    menuToggle.addEventListener("click", (event) => {
        event.stopPropagation();
        navMenu.classList.toggle("active");
    });

    navMenu.addEventListener("click", (event) => {
        event.stopPropagation();
    });

    document.addEventListener("click", () => {
        navMenu.classList.remove("active");
    });

    window.addEventListener("resize", () => {
        if (window.innerWidth > 900) {
            navMenu.classList.remove("active");
        }
    });
}

/* ================================
   Language Switcher
   ================================ */

function setupLanguageSwitcher() {
    const languageButtons = document.querySelectorAll(".language-btn");
    const translatableElements = document.querySelectorAll("[data-en][data-es]");

    if (!languageButtons.length || !translatableElements.length) return;

    function setLanguage(language) {
        document.documentElement.lang = language;

        translatableElements.forEach((element) => {
            element.textContent = element.dataset[language];
        });

        languageButtons.forEach((button) => {
            const isActive = button.dataset.lang === language;
            button.classList.toggle("active", isActive);
            button.style.background = isActive ? "#2563a6" : "#ffffff";
        });

        localStorage.setItem("jccs-language", language);

        const languageChangeEvent = new CustomEvent("jccsLanguageChange", {
            detail: { language }
        });
        document.dispatchEvent(languageChangeEvent);
    }

    languageButtons.forEach((button) => {
        button.addEventListener("click", () => {
            setLanguage(button.dataset.lang);
        });
    });

    setLanguage(localStorage.getItem("jccs-language") || "en");
}

/* ================================
   Services Popup
   ================================ */

function setupServicePopups() {
    const serviceButtons = document.querySelectorAll(".service-square");
    const servicePopup = document.getElementById("service-popup");
    const servicePopupClose = document.getElementById("service-popup-close");
    const servicePopupLabel = document.getElementById("service-popup-label");
    const servicePopupTitle = document.getElementById("service-popup-title");
    const servicePopupText = document.getElementById("service-popup-text");

    if (!serviceButtons.length || !servicePopup) return;

    const serviceDetails = {
        "facility-preservation": {
            title: {
                en: "Facility Preservation",
                es: "Preservación de Instalaciones"
            },
            text: {
                en: "Ongoing facility support focused on protecting building function, appearance, safety, and long-term usability. This includes improvement work, preservation planning, and coordinated maintenance for active environments.",
                es: "Apoyo continuo enfocado en proteger la funcionalidad, apariencia, seguridad y uso a largo plazo de las instalaciones. Incluye mejoras, planeación de preservación y mantenimiento coordinado para entornos activos."
            },
        },
        "commercial-construction": {
            title: {
                en: "Commercial Construction",
                es: "Construcción Comercial"
            },
            text: {
                en: "Construction and improvement support for business, healthcare, professional, and institutional environments, with attention to scheduling, site coordination, safety, and dependable execution.",
                es: "Apoyo en construcción y mejoras para entornos empresariales, de salud, profesionales e institucionales, con atención a la programación, coordinación en sitio, seguridad y ejecución confiable."
            },
        },
        "repairs-maintenance": {
            title: {
                en: "Repairs and Maintenance",
                es: "Reparaciones y Mantenimiento"
            },
            text: {
                en: "Reliable repair and maintenance work to keep facilities safe, functional, clean, and ready for daily operations, including responsive support for spaces that remain in use.",
                es: "Trabajos confiables de reparación y mantenimiento para mantener las instalaciones seguras, funcionales, limpias y listas para operar diariamente, incluyendo apoyo ágil en espacios activos."
            },
        },
        "medical-facility-renovation": {
            title: {
                en: "Medical Facility Renovation",
                es: "Renovación de Instalaciones Médicas"
            },
            text: {
                en: "Renovation support for active medical environments where cleanliness, access, safety, containment, and operational continuity are essential to protect staff, visitors, and patient-facing areas.",
                es: "Apoyo en renovación para entornos médicos activos donde la limpieza, el acceso, la seguridad, la contención y la continuidad operativa son esenciales para proteger al personal, visitantes y áreas de atención."
            },
        },
        "interior-buildouts": {
            title: {
                en: "Interior Buildouts",
                es: "Adecuaciones Interiores"
            },
            text: {
                en: "Interior improvements, modifications, and buildouts designed around client needs, facility use, workflow, finish quality, and project coordination inside active spaces.",
                es: "Mejoras, modificaciones y adecuaciones interiores diseñadas alrededor de las necesidades del cliente, uso de la instalación, flujo de trabajo, calidad de acabados y coordinación del proyecto."
            },
        },
        "code-compliant-renovation": {
            title: {
                en: "Code-Compliant Renovation",
                es: "Renovación Bajo Código"
            },
            text: {
                en: "Renovation work approached with awareness of health, safety, facility, and construction requirements for active commercial and healthcare-related environments.",
                es: "Trabajos de renovación abordados con conciencia de requisitos de salud, seguridad, instalaciones y construcción para entornos comerciales activos y relacionados con la salud."
            },
        }
    };

    function getCurrentLanguage() {
        return localStorage.getItem("jccs-language") || document.documentElement.lang || "en";
    }

    function openServicePopup(serviceKey) {
        const detail = serviceDetails[serviceKey];
        if (!detail || !servicePopup) return;

        const currentLanguage = getCurrentLanguage();

        servicePopup.dataset.activeService = serviceKey;
        servicePopupTitle.textContent = detail.title[currentLanguage];
        servicePopupText.textContent = detail.text[currentLanguage];
        servicePopupLabel.textContent = currentLanguage === "es" ? "Descripción del Servicio" : "Service Overview";

        servicePopup.classList.add("active");
        servicePopup.setAttribute("aria-hidden", "false");
        servicePopup.scrollIntoView({ behavior: "smooth", block: "center" });
    }

    function closeServicePopup() {
        servicePopup.classList.remove("active");
        servicePopup.setAttribute("aria-hidden", "true");
    }

    serviceButtons.forEach((button) => {
        button.addEventListener("click", () => {
            openServicePopup(button.dataset.service);
        });
    });

    if (servicePopupClose) {
        servicePopupClose.addEventListener("click", closeServicePopup);
    }

    document.addEventListener("jccsLanguageChange", () => {
        if (!servicePopup.classList.contains("active")) return;

        const activeService = servicePopup.dataset.activeService;
        if (activeService) {
            openServicePopup(activeService);
        }
    });
}

/* ================================
   Quote Request Form
   ================================ */

function setupQuoteForm() {
    const form = document.getElementById("quote-form");
    if (!form) return;

    const status = document.getElementById("quote-status");
    const submitButton = form.querySelector("button[type='submit']");
    const startedAt = form.querySelector("input[name='started_at']");
    if (startedAt) startedAt.value = String(Date.now());

    const messages = {
        sending: { en: "Sending your request…", es: "Enviando su solicitud…" },
        sent: { en: "Thank you. Your request was sent and our team will follow up soon.", es: "Gracias. Su solicitud fue enviada y nuestro equipo se comunicará pronto." },
        failed: { en: "Your request could not be sent. Please try again in a few minutes.", es: "No se pudo enviar su solicitud. Intente de nuevo en unos minutos." }
    };

    function lang() {
        return document.documentElement.lang === "es" ? "es" : "en";
    }

    function show(kind, text) {
        status.hidden = false;
        status.dataset.kind = kind;
        status.textContent = text || messages[kind][lang()];
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        if (!form.reportValidity()) return;

        submitButton.disabled = true;
        show("sending");

        try {
            const response = await fetch(form.action, {
                method: "POST",
                body: new FormData(form),
                headers: { Accept: "application/json" }
            });
            const result = await response.json().catch(() => ({}));

            if (response.ok && result.ok) {
                form.reset();
                if (startedAt) startedAt.value = String(Date.now());
                show("sent");
            } else {
                show("failed", result.error && result.error[lang()]);
            }
        } catch (error) {
            show("failed");
        } finally {
            submitButton.disabled = false;
        }
    });
}
