# Éléments non vérifiés / Limitations connues — Sunu Santé (Étape 13)

## 1. Tests de concurrence réels (MySQL)
- **Statut** : Non exécutés en CI
- **Raison** : Les tests de concurrence (`FOR UPDATE`, deadlock retry) ne sont testés que sur SQLite (qui n'implémente pas `FOR UPDATE`). Les tests MySQL réels nécessitent une base `sunu_sante_test` configurée et un fichier `phpunit.mysql.xml`.
- **Impact** : La logique de verrouillage (`lockForUpdate` + retry deadlock) est implémentée mais non prouvée en conditions réelles de concurrence MySQL.
- **Action requise** : Configurer MySQL test + `phpunit.mysql.xml` pour valider en conditions réelles.

## 2. Accessibilité complète (WCAG 2.1 AA)
- **Statut** : Partiellement vérifié
- **Couvrant** : Focus visible, `prefers-reduced-motion`, tailles cibles tactiles, structure sémantique
- **Non vérifié** : Audit complet WCAG 2.1 AA (contraste exact sur tous les états, navigation clavier complète, lecteurs d'écran, zoom 200%)
- **Outil suggéré** : axe-core, Lighthouse, test manuel NVDA/VoiceOver

## 3. Tests de charge / performance
- **Statut** : Non réalisés
- **Manquant** : Tests de charge (simulation utilisateurs simultanés), temps de réponse API, optimisation requêtes N+1, index BDD

## 4. Sécurité avancée
- **Non testé** : Pentest, scan OWASP ZAP, audit dépendances (`composer audit`, `npm audit`), CSP headers, HSTS, rate limiting API
- **Manquant** : Tests d'injection SQL avancée, XSS stocké/réfléchi, CSRF sur toutes les routes

## 5. Compatibilité navigateurs
- **Testé** : Chrome/Edge/Firefox récents (desktop + mobile responsive)
- **Non testé** : Safari (iOS/macOS), anciens navigateurs, mode hors-ligne (PWA non implémenté)

## 6. Internationalisation (i18n)
- **Statut** : FR uniquement (touches wolof uniquement)
- **Manquant** : Support multi-langues, RTL, formats date/heure locaux, devise

## 7. Temps réel (WebSockets)
- **Statut** : `wire:poll` uniquement (rafraîchissement polling)
- **Manquant** : Laravel Reverb / Pusher pour temps réel vrai (pas de polling), notifications push

## 8. Monitoring / Observabilité
- **Manquant** : Logs structurés (JSON), métriques (Prometheus/Grafana), alerting, tracing (OpenTelemetry), health checks avancés

## 9. CI/CD
- **Manquant** : Pipeline GitHub Actions / GitLab CI (tests auto, lint, build, deploy), environments staging/prod, rollback

## 9. Documentation technique
- **Manquant** : Documentation API (OpenAPI/Swagger), diagrammes architecture à jour (Mermaid/PlantUML), runbooks opérations

## 10. Données / RGPD
- **Non vérifié** : Droit à l'oubli (suppression compte), portabilité données, consentement cookies, DPO, registre traitements
- **Manquant** : Anonymisation données tests, purge automatique comptes inactifs

## 11. Accessibilité avancée
- **Non testé** : Navigation clavier complète (tab order, focus trap modals), ARIA labels complets, skip links, zoom 200% sans perte contenu

## 12. Tests de régression visuelle
- **Manquant** : Tests screenshots (Percy/Chromatic), détection régressions CSS

## 13. Gestion erreurs / Resilience
- **Partiel** : Try/catch services, logs Activitylog
- **Manquant** : Circuit breaker, retry policies externes, fallback gracieux, dead letter queue

## 14. Multi-tenancy / Évolutivité
- **Non prévu** : Architecture single-tenant, pas de sharding, pas de read replicas

---

## Résumé priorités pour production

| Priorité | Élément | Effort estimé |
|----------|---------|---------------|
| 🔴 Critique | Tests concurrence MySQL réels | 2-3 jours |
| 🔴 Critique | Audit sécurité (OWASP) | 3-5 jours |
| 🟠 Important | Audit accessibilité WCAG 2.1 AA | 2-3 jours |
| 🟠 Important | Pipeline CI/CD | 2-3 jours |
| 🟡 Souhaitable | Tests de charge | 2-3 jours |
| 🟡 Souhaitable | Monitoring/Alerting | 2-3 jours |
| 🟢 Optionnel | PWA / Mode hors-ligne | 1-2 semaines |
| 🟢 Optionnel | Internationalisation | 1-2 semaines |

---

*Document généré à l'étape 13 — Sunu Santé PFE*
*Dernière mise à jour : 2026-10-13*