/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  EventHub Pro — assets/js/app.js                            ║
 * ║  JavaScript principal — Fetch API & interactions            ║
 * ║  ENSA Marrakech — Examen PHP Avancé                         ║
 * ╚══════════════════════════════════════════════════════════════╝
 *
 * STATUT : ⚠️ Partiel — Fonctions incomplètes (marquées TODO)
 *
 * CE QUI EST FOURNI :
 *   ✅  renderEventCards()     — rendu HTML des cartes
 *   ✅  showToast()            — notifications toast
 *   ✅  showSkeletons()        — squelettes de chargement
 *   ✅  setButtonLoading()     — état de chargement sur les boutons
 *   ✅  animateCounter()       — animation de compteurs
 *   ✅  formatDate()           — formatage de date en français
 *
 * À COMPLÉTER (Parties 4.1 et 4.2) :
 *   🔴  loadEvents()          — chargement via fetch + filtres
 *   🔴  registerToEvent()     — inscription via POST fetch
 *   🔴  debounceSearch()      — recherche live avec délai 400ms
 *   🔴  startDashboard()      — polling toutes les 30s
 *   🔴  fetchDashboardStats() — appel api/stats.php
 *
 * CONTRAINTES :
 *   → JavaScript vanilla uniquement (pas de jQuery, pas d'Axios)
 *   → Tous les fetch() doivent gérer les erreurs réseau (try/catch)
 *   → L'interface ne doit jamais "casser" en cas d'erreur API
 */

'use strict';

// ══════════════════════════════════════════════════════════════════════════
// ÉTAT GLOBAL
// ══════════════════════════════════════════════════════════════════════════
const STATE = {
    currentTab:    'all',       // Onglet actif : 'all' | 'upcoming' | 'full'
    dashInterval:  null,        // Référence setInterval du dashboard
    debounceTimer: null,        // Référence setTimeout du debounce
    selectedEvent: null,        // Événement sélectionné pour inscription
};

const CATEGORY_COLORS = {
    tech:     { bg: '#DBEAFE', text: '#1D4ED8', primary: '#2563EB' },
    design:   { bg: '#EDE9FE', text: '#6D28D9', primary: '#7C3AED' },
    business: { bg: '#FEF3C7', text: '#B45309', primary: '#EA580C' },
    science:  { bg: '#DCFCE7', text: '#15803D', primary: '#16A34A' },
};


// ══════════════════════════════════════════════════════════════════════════
// TODO 4.1 — CHARGEMENT DES ÉVÉNEMENTS
// ══════════════════════════════════════════════════════════════════════════

/**
 * Charge les événements depuis api/events.php et les affiche.
 *
 * PARAMÈTRES À ENVOYER EN POST (JSON) :
 *   keyword, category, has_places, tab (STATE.currentTab), page
 *
 * EN CAS DE SUCCÈS :
 *   → Appeler renderEventCards(data.data)
 *   → Mettre à jour la pagination si data.meta.pages > 1
 *
 * EN CAS D'ERREUR RÉSEAU :
 *   → showToast('Impossible de charger les événements.', 'error')
 *   → Afficher un message d'erreur dans la grille (pas de page blanche)
 *
 * LOADING STATE :
 *   → Appeler showSkeletons() avant le fetch
 *   → Les remplacer par les vraies cartes après réception
 *
 * @returns {Promise<void>}
 */
async function loadEvents() {
    // TODO 4.1 — Implémentez cette fonction
    //
    // const keyword   = document.getElementById('search-input').value;
    // const category  = document.getElementById('filter-category').value;
    // const hasPlaces = document.getElementById('filter-places').value === '1';
    //
    // showSkeletons();
    //
    // try {
    //     const response = await fetch('api/events.php', {
    //         method:  'POST',
    //         headers: { 'Content-Type': 'application/json' },
    //         body:    JSON.stringify({ keyword, category, has_places: hasPlaces, tab: STATE.currentTab })
    //     });
    //
    //     if (!response.ok) throw new Error('HTTP ' + response.status);
    //
    //     const data = await response.json();
    //
    //     if (data.success) {
    //         renderEventCards(data.data);
    //     } else {
    //         showGridError(data.error ?? 'Erreur inconnue.');
    //     }
    //
    // } catch (err) {
    //     console.error('[loadEvents]', err);
    //     showToast('Impossible de charger les événements.', 'error');
    //     showGridError('Erreur de connexion au serveur.');
    // }

    console.warn('[TODO] loadEvents() non implémenté — Partie 4.1');
}


// ══════════════════════════════════════════════════════════════════════════
// TODO 4.1 — INSCRIPTION EN TEMPS RÉEL
// ══════════════════════════════════════════════════════════════════════════

/**
 * Soumet l'inscription d'un participant à un événement.
 *
 * DONNÉES À ENVOYER (POST JSON) :
 *   { event_id, name, email }
 *
 * EN CAS DE SUCCÈS (data.success === true) :
 *   → Fermer la modale d'inscription
 *   → showToast('Inscription réussie ! Ticket envoyé par email.', 'success')
 *   → Mettre à jour la barre de capacité de la carte SANS rechargement :
 *       document.getElementById('bar-' + eventId).style.width = data.capacity_pct + '%'
 *       document.getElementById('places-' + eventId).textContent = ...
 *   → Si data.is_full === true : désactiver le bouton d'inscription
 *   → Si data.alert_sent === true : showToast('Alerte 80% envoyée à l\'organisateur', 'info')
 *
 * EN CAS D'ERREUR :
 *   → showToast(data.error, 'error')
 *   → Ne pas fermer la modale
 *
 * @param {number} eventId
 * @param {string} name
 * @param {string} email
 * @returns {Promise<void>}
 */
async function registerToEvent(eventId, name, email) {
    // TODO 4.1 — Implémentez cette fonction
    //
    // setButtonLoading('btn-register', true, 'Inscription en cours…');
    //
    // try {
    //     const response = await fetch('events/register.php', {
    //         method:  'POST',
    //         headers: { 'Content-Type': 'application/json' },
    //         body:    JSON.stringify({ event_id: eventId, name, email })
    //     });
    //
    //     const data = await response.json();
    //
    //     if (data.success) {
    //         closeRegisterModal();
    //         showToast('Inscription réussie ! Votre ticket PDF vous sera envoyé par email.', 'success');
    //         // Mise à jour temps réel de la carte...
    //     } else {
    //         showToast(data.error ?? 'Erreur lors de l\'inscription.', 'error');
    //     }
    //
    // } catch (err) {
    //     console.error('[registerToEvent]', err);
    //     showToast('Erreur réseau. Veuillez réessayer.', 'error');
    // } finally {
    //     setButtonLoading('btn-register', false, "S'inscrire");
    // }

    console.warn('[TODO] registerToEvent() non implémenté — Partie 4.1');
}


// ══════════════════════════════════════════════════════════════════════════
// TODO 4.1 — RECHERCHE AVEC DEBOUNCE
// ══════════════════════════════════════════════════════════════════════════

/**
 * Déclenche loadEvents() après un délai de 400ms sans frappe.
 * Annule le timer précédent si l'utilisateur tape encore.
 *
 * APPELÉ PAR : oninput sur #search-input
 *
 * EXEMPLE D'IMPLÉMENTATION ATTENDUE :
 *   clearTimeout(STATE.debounceTimer);
 *   STATE.debounceTimer = setTimeout(loadEvents, 400);
 */
function debounceSearch() {
    // TODO 4.1 — Implémentez le debounce (3 lignes)
    console.warn('[TODO] debounceSearch() non implémenté — Partie 4.1');
}


// ══════════════════════════════════════════════════════════════════════════
// TODO 4.2 — DASHBOARD TEMPS RÉEL
// ══════════════════════════════════════════════════════════════════════════

/**
 * Démarre le polling automatique du dashboard (toutes les 30s).
 * Appelle fetchDashboardStats() immédiatement puis toutes les 30 secondes.
 * Arrête le polling précédent si la fonction est rappelée.
 */
function startDashboard() {
    // TODO 4.2 — Implémentez le polling
    //
    // if (STATE.dashInterval) clearInterval(STATE.dashInterval);
    // fetchDashboardStats();
    // STATE.dashInterval = setInterval(fetchDashboardStats, 30000);

    console.warn('[TODO] startDashboard() non implémenté — Partie 4.2');
}

/**
 * Récupère les statistiques depuis api/stats.php et met à jour le dashboard.
 *
 * EN CAS DE SUCCÈS :
 *   → Mettre à jour les KPI (animateCounter pour les nombres)
 *   → Mettre à jour le Top 3
 *   → Mettre à jour l'heure de dernière mise à jour
 *   → Si un événement vient de passer à 100% → showToast(..., 'info')
 *
 * EN CAS D'ERREUR :
 *   → Afficher un message discret (ne pas casser l'interface)
 *   → Réessayer automatiquement dans 10 secondes (clearInterval + setTimeout)
 *
 * @returns {Promise<void>}
 */
async function fetchDashboardStats() {
    // TODO 4.2 — Implémentez cette fonction
    //
    // try {
    //     const response = await fetch('api/stats.php');
    //     if (!response.ok) throw new Error('HTTP ' + response.status);
    //
    //     const data = await response.json();
    //     if (!data.success) throw new Error(data.error);
    //
    //     // Mise à jour KPI
    //     animateCounter('kpi-total',    data.summary.total_registered);
    //     animateCounter('kpi-new-24h',  data.summary.new_last_24h);
    //     animateCounter('kpi-alertes',  data.summary.alert_count);
    //     document.getElementById('kpi-taux').textContent = data.summary.avg_fill_pct + '%';
    //
    //     // Top 3
    //     renderTop3(data.top3);
    //
    //     // Horodatage
    //     document.getElementById('last-update').textContent =
    //         'Mis à jour à ' + new Date().toLocaleTimeString('fr-FR');
    //
    // } catch (err) {
    //     console.error('[fetchDashboardStats]', err);
    //     // Réessayer dans 10s
    //     clearInterval(STATE.dashInterval);
    //     setTimeout(() => {
    //         startDashboard();
    //     }, 10000);
    //     showToast('Erreur de chargement du dashboard. Nouvelle tentative dans 10s.', 'error');
    // }

    console.warn('[TODO] fetchDashboardStats() non implémenté — Partie 4.2');
}


// ══════════════════════════════════════════════════════════════════════════
// FOURNI — RENDU DES CARTES D'ÉVÉNEMENTS
// ══════════════════════════════════════════════════════════════════════════

/**
 * Génère et injecte les cartes d'événements dans #events-grid.
 *
 * @param {Array} events  Tableau d'objets événement (depuis api/events.php)
 */
function renderEventCards(events) {
    const grid = document.getElementById('events-grid');

    if (!events || events.length === 0) {
        grid.innerHTML = `
            <div class="col-span-3 text-center py-16">
                <div class="text-5xl mb-4">🔍</div>
                <p class="font-display font-bold text-slate-600 text-lg">Aucun événement trouvé</p>
                <p class="text-slate-400 text-sm mt-2">Modifiez vos critères de recherche</p>
            </div>`;
        return;
    }

    grid.innerHTML = events.map(e => {
        const pct      = parseInt(e.fill_percentage) || 0;
        const isFull   = e.available_places <= 0;
        const isWarn   = pct >= 80 && !isFull;
        const colors   = CATEGORY_COLORS[e.category] || { bg: '#F1F5F9', text: '#334155', primary: '#64748B' };
        const barColor = isFull ? '#DC2626' : isWarn ? '#F59E0B' : colors.primary;

        return `
        <div class="event-card bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col shadow-sm"
             data-event-id="${e.id}">
            <div class="h-2" style="background:${colors.primary}"></div>
            <div class="p-5 flex flex-col flex-1">
                <div class="flex items-start gap-2 mb-3 flex-wrap">
                    <span class="badge" style="background:${colors.bg};color:${colors.text}">${e.category}</span>
                    ${isFull  ? '<span class="badge" style="background:#FEE2E2;color:#DC2626">Complet</span>' : ''}
                    ${isWarn  ? '<span class="badge" style="background:#FEF3C7;color:#B45309">🔥 Quasi plein</span>' : ''}
                </div>
                <h3 class="font-display font-bold text-base text-slate-900 mb-1 leading-snug">${e.title}</h3>
                <p class="text-xs text-slate-500 mb-1">📅 ${formatDate(e.event_date)}</p>
                <p class="text-xs text-slate-500 mb-3">📍 ${e.location}</p>
                <p class="text-xs text-slate-600 leading-relaxed flex-1">${e.description}</p>
                <div class="mt-4">
                    <div class="flex justify-between text-xs font-display font-bold mb-1">
                        <span class="text-slate-400">Capacité</span>
                        <span style="color:${barColor}" id="places-${e.id}">
                            ${e.registered_count} / ${e.capacity}
                        </span>
                    </div>
                    <div class="cap-bar">
                        <div class="cap-bar-fill" id="bar-${e.id}"
                             style="width:${pct}%; background:${barColor}"></div>
                    </div>
                    ${!isFull ? `<p class="text-xs text-slate-400 mt-1">${e.available_places} place(s) restante(s)</p>` : ''}
                </div>
                <button
                    id="btn-${e.id}"
                    ${isFull ? 'disabled' : `onclick="openRegisterModal(${e.id})"`}
                    class="mt-4 w-full py-2.5 rounded-xl font-display font-bold text-xs text-white tracking-wide
                           ${isFull ? 'opacity-40 cursor-not-allowed' : 'hover:opacity-90 transition'}"
                    style="background:${isFull ? '#94A3B8' : colors.primary}">
                    ${isFull ? 'Complet' : "S'inscrire →"}
                </button>
            </div>
        </div>`;
    }).join('');
}


// ══════════════════════════════════════════════════════════════════════════
// FOURNI — UTILITAIRES
// ══════════════════════════════════════════════════════════════════════════

/** Affiche les squelettes de chargement dans la grille. */
function showSkeletons(count = 3) {
    const grid = document.getElementById('events-grid');
    grid.innerHTML = Array.from({ length: count }, () => `
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="skeleton h-2 w-full mb-4 -mx-5 -mt-5" style="width:calc(100% + 40px); border-radius:0"></div>
            <div class="skeleton h-5 w-3/4 mb-2 mt-2"></div>
            <div class="skeleton h-3 w-1/2 mb-1"></div>
            <div class="skeleton h-3 w-2/3 mb-4"></div>
            <div class="skeleton h-2 w-full mb-4"></div>
            <div class="skeleton h-9 w-28 rounded-xl"></div>
        </div>`).join('');
}

/** Affiche un message d'erreur dans la grille. */
function showGridError(message) {
    document.getElementById('events-grid').innerHTML = `
        <div class="col-span-3 text-center py-16">
            <div class="text-5xl mb-4">⚠️</div>
            <p class="font-display font-bold text-red-600">${message}</p>
            <button onclick="loadEvents()"
                    class="mt-4 px-6 py-2 rounded-lg text-sm font-display font-bold text-white"
                    style="background:#2563eb">Réessayer</button>
        </div>`;
}

/**
 * Affiche un toast de notification.
 * @param {string} message
 * @param {'success'|'error'|'info'} type
 */
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    const toast     = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.cssText = 'opacity:0; transform:translateX(120%); transition:all .3s ease;';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

/**
 * Met un bouton en état de chargement (spinner).
 * @param {string} buttonId
 * @param {boolean} loading
 * @param {string} loadingText
 */
function setButtonLoading(buttonId, loading, loadingText = 'Chargement…') {
    const btn = document.getElementById(buttonId);
    if (!btn) return;
    btn.disabled = loading;
    if (loading) {
        btn.dataset.originalText = btn.textContent;
        btn.innerHTML = `<span class="spinner"></span> ${loadingText}`;
    } else {
        btn.innerHTML = btn.dataset.originalText || loadingText;
    }
}

/**
 * Anime un compteur de 0 à target.
 * @param {string} elementId
 * @param {number} target
 */
function animateCounter(elementId, target) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const start = parseInt(el.textContent) || 0;
    const diff  = target - start;
    const steps = 24;
    let   step  = 0;
    const timer = setInterval(() => {
        step++;
        el.textContent = Math.round(start + diff * (step / steps));
        if (step >= steps) { el.textContent = target; clearInterval(timer); }
    }, 20);
}

/**
 * Formate une date ISO en français lisible.
 * @param {string} dateStr  Format: '2025-09-20T09:00:00'
 * @returns {string}        Format: 'sam. 20 sept. 2025 à 09h00'
 */
function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('fr-FR', {
        weekday: 'short', day: 'numeric', month: 'short',
        year: 'numeric', hour: '2-digit', minute: '2-digit'
    }).replace(':', 'h');
}


// ══════════════════════════════════════════════════════════════════════════
// INITIALISATION
// ══════════════════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    loadEvents();
});
