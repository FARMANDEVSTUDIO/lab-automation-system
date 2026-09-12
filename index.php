<?php
declare(strict_types=1);
// Homepage — landing page with feature showcase and scroll animations (Arsalan)
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){ var t = localStorage.getItem('lab_theme') || 'dark'; document.documentElement.setAttribute('data-theme', t); })();</script>
<title><?= e(APP_NAME) ?> — Laboratory Testing, Automated</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<style>
/* ════════════════════════════════════════════════════════════════════════════
   LANDING PAGE — Premium Laboratory Automation
   ════════════════════════════════════════════════════════════════════════════ */

/* ── Foundation ── */
.lp { --lp-max: 1200px; --lp-gutter: 48px; --lp-section: 112px; }

.lp h1,.lp h2,.lp h3,.lp h4 { font-family: var(--font-display); }

.lp-wrap {
  max-width: var(--lp-max);
  margin: 0 auto;
  padding: 0 var(--lp-gutter);
}

/* ── Hero Entrance Animation ── */
.lp-hero .lp-hero-content > * {
  opacity: 0;
  transform: translateY(28px) scale(0.97);
  animation: heroEmerge 0.7s cubic-bezier(0.16,1,0.3,1) both;
}
.lp-hero .lp-eyebrow { animation-delay: 0.05s; }
.lp-hero h1 { animation-delay: 0.15s; }
.lp-hero .lp-hero-desc { animation-delay: 0.25s; }
.lp-hero .lp-hero-actions { animation-delay: 0.35s; }
.lp-hero .lp-hero-meta { animation-delay: 0.45s; }
.lp-hero .lp-hero-visual {
  opacity: 0;
  animation: heroEmerge 0.85s cubic-bezier(0.16,1,0.3,1) 0.2s both;
}
@keyframes heroEmerge {
  from { opacity: 0; transform: translateY(28px) scale(0.97); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}
@media (prefers-reduced-motion: reduce) {
  .lp-hero .lp-hero-content > *,
  .lp-hero .lp-hero-visual { animation: none; opacity: 1; transform: none; }
}

/* ── Background Atmospheric Motion ── */
.lp-hero::before { animation: heroBgFloat 28s ease-in-out infinite alternate; }
@keyframes heroBgFloat {
  0%   { transform: translate(0, 0); }
  100% { transform: translate(12px, -8px); }
}
.lp-cta::before { animation: ctaBgFloat 22s ease-in-out infinite alternate; }
@keyframes ctaBgFloat {
  0%   { transform: translateX(-50%) translate(0, 0); }
  100% { transform: translateX(-50%) translate(-8px, 6px); }
}
@media (prefers-reduced-motion: reduce) {
  .lp-hero::before, .lp-cta::before { animation: none; }
}

/* ── Nav ── */
.lp-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 var(--lp-gutter);
  height: 64px;
  border-bottom: 1px solid var(--line);
  position: sticky;
  top: 0;
  z-index: 50;
  backdrop-filter: blur(16px) saturate(1.8);
  background: rgba(15,11,12,0.88);
}
:root[data-theme="light"] .lp-nav { background: rgba(250,243,236,0.88); }

.lp-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1rem;
  color: var(--ink);
  text-decoration: none;
}
.lp-brand:hover { color: var(--ink); }

.lp-nav-center {
  display: flex;
  align-items: center;
  gap: 4px;
}
.lp-nav-link {
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--ink-soft);
  padding: 6px 14px;
  border-radius: var(--radius-sm);
  transition: color var(--duration) var(--ease), background var(--duration) var(--ease);
  text-decoration: none;
}
.lp-nav-link:hover { color: var(--ink); background: var(--accent-subtle); }
.lp-nav-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* ════════════════════════════════════════════════════════════════════════════
   HERO — Split layout: Text left, Instrument visual right
   ════════════════════════════════════════════════════════════════════════════ */
.lp-hero {
  position: relative;
  padding: 80px 0 96px;
  overflow: hidden;
}
.lp-hero::before {
  content: '';
  position: absolute;
  top: -200px;
  right: -100px;
  width: 900px;
  height: 700px;
  background:
    radial-gradient(ellipse at 60% 40%, rgba(166,35,28,0.15) 0%, transparent 60%),
    radial-gradient(ellipse at 30% 70%, rgba(184,68,63,0.08) 0%, transparent 50%);
  pointer-events: none;
  z-index: 0;
}
.lp-hero > * { position: relative; z-index: 1; }

.lp-hero-grid {
  display: grid;
  grid-template-columns: 5fr 6fr;
  gap: 64px;
  align-items: center;
}
.lp-hero-content { max-width: 520px; }

.lp-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 16px;
  border-radius: var(--radius-full);
  background: var(--accent-tint);
  border: 1px solid rgba(166,35,28,0.3);
  color: var(--accent-strong);
  font-size: 0.6875rem;
  font-weight: 600;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  margin-bottom: 24px;
}
.lp-eyebrow-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: var(--accent-strong);
  animation: eyebrowPulse 2s ease-in-out infinite;
}
@keyframes eyebrowPulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}

.lp-hero h1 {
  font-size: 3.25rem;
  font-weight: 700;
  letter-spacing: -0.03em;
  line-height: 1.1;
  color: var(--ink);
  margin-bottom: 20px;
}
.lp-hero h1 span {
  color: var(--accent-strong);
  position: relative;
}

.lp-hero-desc {
  font-size: 1.0625rem;
  line-height: 1.7;
  color: var(--ink-soft);
  margin-bottom: 32px;
  max-width: 460px;
}

.lp-hero-actions {
  display: flex;
  gap: 12px;
  margin-bottom: 48px;
}
.lp-hero-actions .btn { padding: 12px 28px; font-size: 0.875rem; }

.lp-hero-meta {
  display: flex;
  gap: 32px;
}
.lp-hero-meta-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.lp-hero-meta-value {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.25rem;
  color: var(--ink);
  font-variant-numeric: tabular-nums;
}
.lp-hero-meta-label {
  font-size: 0.6875rem;
  color: var(--ink-faint);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  font-weight: 500;
}

/* Hero visual — Laboratory instrument panel */
.lp-hero-visual {
  position: relative;
}
.lp-instrument {
  position: relative;
  background: var(--charcoal);
  border-radius: 12px;
  overflow: hidden;
  box-shadow:
    0 0 0 1px rgba(255,255,255,0.06),
    0 32px 80px -12px rgba(0,0,0,0.6),
    0 0 120px -40px rgba(166,35,28,0.2);
  aspect-ratio: 4/3;
}
:root[data-theme="light"] .lp-instrument {
  background: #1C1416;
  box-shadow:
    0 0 0 1px rgba(0,0,0,0.1),
    0 32px 80px -12px rgba(0,0,0,0.25),
    0 0 120px -40px rgba(166,35,28,0.08);
}

.lp-instrument::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -30%;
  width: 80%;
  height: 80%;
  background: radial-gradient(circle, rgba(166,35,28,0.12) 0%, transparent 70%);
  pointer-events: none;
}

.lp-inst-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px;
  background: rgba(255,255,255,0.03);
  border-bottom: 1px solid rgba(255,255,255,0.06);
}
.lp-inst-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.75rem;
  font-weight: 600;
  color: rgba(255,255,255,0.7);
  letter-spacing: 0.02em;
}
.lp-inst-live {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: #6FA383;
  box-shadow: 0 0 8px rgba(111,163,131,0.5);
  animation: livePulse 1.5s ease-in-out infinite;
}
@keyframes livePulse {
  0%, 100% { opacity: 1; box-shadow: 0 0 8px rgba(111,163,131,0.5); }
  50% { opacity: 0.6; box-shadow: 0 0 4px rgba(111,163,131,0.3); }
}
.lp-inst-badge {
  font-size: 0.5625rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: rgba(111,163,131,0.9);
  background: rgba(111,163,131,0.12);
  border: 1px solid rgba(111,163,131,0.2);
  padding: 2px 10px;
  border-radius: var(--radius-full);
}

.lp-inst-body { padding: 20px; }

.lp-inst-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
  margin-bottom: 16px;
}
.lp-inst-stat {
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 6px;
  padding: 12px;
}
.lp-inst-stat-label {
  font-size: 0.5rem;
  font-weight: 600;
  color: rgba(255,255,255,0.3);
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: 6px;
}
.lp-inst-stat-value {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.125rem;
  color: rgba(255,255,255,0.85);
  font-variant-numeric: tabular-nums;
}
.lp-inst-stat-value.pass { color: rgba(111,163,131,0.9); }

/* Tolerance band visualization */
.lp-inst-tolerance {
  background: rgba(255,255,255,0.02);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 6px;
  padding: 16px;
  margin-bottom: 12px;
}
.lp-inst-tol-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}
.lp-inst-tol-title {
  font-size: 0.625rem;
  font-weight: 600;
  color: rgba(255,255,255,0.4);
  text-transform: uppercase;
  letter-spacing: 0.08em;
}
.lp-inst-tol-reading {
  font-family: var(--font-mono);
  font-size: 0.6875rem;
  color: rgba(111,163,131,0.9);
  font-weight: 600;
}
.lp-tol-track {
  position: relative;
  height: 28px;
  background: rgba(255,255,255,0.04);
  border-radius: 4px;
  overflow: hidden;
}
.lp-tol-band {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 15%;
  right: 15%;
  background: rgba(111,163,131,0.1);
  border-left: 2px dashed rgba(111,163,131,0.3);
  border-right: 2px dashed rgba(111,163,131,0.3);
}
.lp-tol-marker {
  position: absolute;
  top: 2px;
  bottom: 2px;
  left: 58%;
  width: 3px;
  background: rgba(111,163,131,0.9);
  border-radius: 2px;
  box-shadow: 0 0 8px rgba(111,163,131,0.4);
}
.lp-tol-labels {
  display: flex;
  justify-content: space-between;
  margin-top: 6px;
  font-family: var(--font-mono);
  font-size: 0.5rem;
  color: rgba(255,255,255,0.25);
  font-variant-numeric: tabular-nums;
}

/* Parameter rows */
.lp-inst-params {
  display: flex;
  flex-direction: column;
  gap: 1px;
  background: rgba(255,255,255,0.03);
  border-radius: 6px;
  overflow: hidden;
}
.lp-inst-param {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr auto;
  gap: 8px;
  align-items: center;
  padding: 8px 12px;
  background: rgba(15,11,12,0.6);
  font-size: 0.625rem;
  color: rgba(255,255,255,0.5);
}
.lp-inst-param:first-child {
  font-weight: 600;
  color: rgba(255,255,255,0.3);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  font-size: 0.5rem;
  background: rgba(255,255,255,0.03);
}
.lp-param-name { font-weight: 500; color: rgba(255,255,255,0.65); }
.lp-param-val { font-family: var(--font-mono); font-variant-numeric: tabular-nums; color: rgba(255,255,255,0.7); }
.lp-param-pass {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-weight: 600;
  font-size: 0.5625rem;
}
.lp-param-pass.ok { color: rgba(111,163,131,0.9); }
.lp-param-pass.fail { color: rgba(194,91,78,0.9); }
.lp-param-dot {
  width: 5px; height: 5px;
  border-radius: 50%;
}
.lp-param-dot.ok { background: rgba(111,163,131,0.9); }
.lp-param-dot.fail { background: rgba(194,91,78,0.9); }

/* ════════════════════════════════════════════════════════════════════════════
   SECTION — Problem Statement
   ════════════════════════════════════════════════════════════════════════════ */
.lp-section {
  padding: var(--lp-section) 0;
  position: relative;
}
.lp-section + .lp-section::before {
  content: '';
  position: absolute;
  top: 0; left: 10%; right: 10%;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--line) 25%, var(--line) 75%, transparent);
  pointer-events: none;
}
.lp-section-alt { background: var(--paper-raised); }
.lp-section-dark {
  background: linear-gradient(180deg, transparent 0%, var(--charcoal) 48px, var(--charcoal) calc(100% - 48px), transparent 100%);
  padding-top: calc(var(--lp-section) + 24px);
  padding-bottom: calc(var(--lp-section) + 24px);
}
.lp-section-dark::before,
.lp-section-dark + .lp-section::before { display: none !important; }
:root[data-theme="light"] .lp-section-dark {
  background: linear-gradient(180deg, transparent 0%, #1C1416 48px, #1C1416 calc(100% - 48px), transparent 100%);
  color: #F2E8DC;
}
:root[data-theme="light"] .lp-section-dark h2,
:root[data-theme="light"] .lp-section-dark h3,
:root[data-theme="light"] .lp-section-dark .lp-section-label,
:root[data-theme="light"] .lp-section-dark .lp-wf-name { color: #F2E8DC; }
:root[data-theme="light"] .lp-section-dark .lp-section-sub,
:root[data-theme="light"] .lp-section-dark .lp-wf-desc { color: #B9ACA3; }

.lp-section-label {
  font-size: 0.625rem;
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--accent-strong);
  margin-bottom: 12px;
}

.lp-section h2 {
  font-size: 2rem;
  font-weight: 700;
  letter-spacing: -0.025em;
  line-height: 1.15;
  color: var(--ink);
  margin-bottom: 16px;
}
.lp-section-sub {
  font-size: 1.0625rem;
  line-height: 1.7;
  color: var(--ink-soft);
  max-width: 560px;
}

/* Problem cards */
.lp-problem-grid {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 20px;
  margin-top: 48px;
}
.lp-problem-featured {
  grid-row: span 2;
  display: flex;
  flex-direction: column;
  justify-content: center;
}
.lp-problem-featured h3 { font-size: 1.0625rem; }
.lp-problem-card {
  padding: 28px;
  border-radius: 10px;
  border: 1px solid var(--line);
  background: var(--paper);
  transition: border-color var(--duration-slow) var(--ease), box-shadow var(--duration-slow) var(--ease);
}
.lp-problem-card:hover {
  border-color: var(--line-strong);
  box-shadow: var(--shadow-e1);
}
.lp-problem-num {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 2rem;
  color: var(--accent);
  opacity: 0.35;
  margin-bottom: 12px;
  line-height: 1;
}
.lp-problem-card h3 {
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--ink);
  margin-bottom: 8px;
}
.lp-problem-card p {
  font-size: 0.8125rem;
  line-height: 1.65;
  color: var(--ink-soft);
  margin: 0;
}

/* ════════════════════════════════════════════════════════════════════════════
   SECTION — Capabilities (split layouts)
   ════════════════════════════════════════════════════════════════════════════ */
.lp-split {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 80px;
  align-items: center;
}
.lp-split-reverse { direction: rtl; }
.lp-split-reverse > * { direction: ltr; }

.lp-split-text { max-width: 480px; }
.lp-split-text h3 {
  font-size: 1.375rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: var(--ink);
  margin-bottom: 14px;
}
.lp-split-text p {
  font-size: 0.9375rem;
  line-height: 1.7;
  color: var(--ink-soft);
  margin: 0 0 24px;
}
.lp-split-features {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.lp-split-feature {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  font-size: 0.8125rem;
  color: var(--ink-soft);
  line-height: 1.5;
}
.lp-split-feature-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
  color: var(--accent-strong);
  margin-top: 1px;
}

/* Visual panels for split sections */
.lp-visual-panel {
  background: var(--paper-raised);
  border: 1px solid var(--line);
  border-radius: 10px;
  overflow: hidden;
  box-shadow: var(--shadow-e1);
}
.lp-visual-panel-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  border-bottom: 1px solid var(--line);
  font-size: 0.6875rem;
  font-weight: 600;
  color: var(--ink-faint);
  text-transform: uppercase;
  letter-spacing: 0.06em;
}
.lp-visual-panel-body { padding: 20px; }

/* Product registration visual */
.lp-reg-fields {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.lp-reg-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.lp-reg-label {
  font-size: 0.625rem;
  font-weight: 600;
  color: var(--ink-faint);
  text-transform: uppercase;
  letter-spacing: 0.06em;
}
.lp-reg-input {
  padding: 8px 12px;
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  background: var(--paper);
  font-family: var(--font-mono);
  font-size: 0.8125rem;
  color: var(--ink);
}
.lp-reg-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

/* Tolerance visual */
.lp-tol-visual {
  padding: 28px;
}
.lp-tol-param-row {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 14px 0;
  border-bottom: 1px solid var(--line);
}
.lp-tol-param-row:last-child { border-bottom: none; }
.lp-tol-param-name {
  width: 140px;
  flex-shrink: 0;
  font-weight: 600;
  font-size: 0.8125rem;
  color: var(--ink);
}
.lp-tol-param-bar {
  flex: 1;
  position: relative;
  height: 8px;
  background: var(--line);
  border-radius: 4px;
  overflow: visible;
}
.lp-tol-param-fill {
  position: absolute;
  top: 0; bottom: 0; left: 0;
  border-radius: 4px;
  transition: width 1s var(--ease-out);
}
.lp-tol-param-fill.ok { background: var(--success); }
.lp-tol-param-fill.warn { background: var(--warning); }
.lp-tol-param-fill.fail { background: var(--fail); }
.lp-tol-param-val {
  width: 80px;
  text-align: right;
  font-family: var(--font-mono);
  font-size: 0.8125rem;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
}
.lp-tol-param-status {
  width: 48px;
  text-align: center;
}

/* ════════════════════════════════════════════════════════════════════════════
   SECTION — Workflow (6-step grid)
   ════════════════════════════════════════════════════════════════════════════ */
.lp-wf-track {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-top: 56px;
}
.lp-wf-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  position: relative;
  padding: 32px 20px 28px;
  border-radius: 12px;
  background: rgba(255,255,255,0.025);
  border: 1px solid rgba(255,255,255,0.06);
  transition: border-color 0.4s var(--ease), transform 0.4s var(--ease), box-shadow 0.4s var(--ease);
}
.lp-wf-step:hover {
  border-color: rgba(166,35,28,0.3);
  transform: translateY(-3px);
  box-shadow: 0 12px 32px -8px rgba(166,35,28,0.12);
}
.lp-wf-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(166,35,28,0.1);
  border: 1px solid rgba(166,35,28,0.18);
  color: #C86860;
  margin-bottom: 16px;
  transition: transform 0.3s var(--ease), background 0.3s var(--ease);
}
.lp-wf-step:hover .lp-wf-icon {
  transform: scale(1.06);
  background: rgba(166,35,28,0.18);
}
.lp-wf-num {
  font-family: var(--font-mono);
  font-size: 0.5625rem;
  font-weight: 600;
  color: rgba(255,255,255,0.22);
  letter-spacing: 0.04em;
  margin-bottom: 6px;
}
.lp-wf-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: rgba(255,255,255,0.88);
  margin-bottom: 6px;
}
.lp-wf-desc {
  font-size: 0.75rem;
  color: rgba(255,255,255,0.4);
  max-width: 180px;
  line-height: 1.5;
}
:root[data-theme="light"] .lp-wf-step {
  background: rgba(255,255,255,0.04);
  border-color: rgba(255,255,255,0.08);
}
:root[data-theme="light"] .lp-wf-step:hover {
  border-color: rgba(166,35,28,0.35);
}
:root[data-theme="light"] .lp-wf-icon {
  background: rgba(166,35,28,0.12);
  border-color: rgba(166,35,28,0.22);
  color: #C86860;
}
:root[data-theme="light"] .lp-wf-num { color: rgba(255,255,255,0.28); }

/* ════════════════════════════════════════════════════════════════════════════
   SECTION — Roles
   ════════════════════════════════════════════════════════════════════════════ */
.lp-roles-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-top: 48px;
}
.lp-roles-row2 {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin-top: 16px;
  max-width: 66.66%;
  margin-left: auto;
  margin-right: auto;
}
.lp-role-card {
  padding: 28px 24px;
  border-radius: 10px;
  border: 1px solid var(--line);
  background: var(--paper-raised);
  text-align: left;
  display: flex;
  gap: 16px;
  align-items: flex-start;
  transition: border-color var(--duration-slow) var(--ease), transform var(--duration-slow) var(--ease), box-shadow var(--duration-slow) var(--ease);
}
.lp-role-card:hover {
  border-color: var(--line-strong);
  transform: translateY(-3px);
  box-shadow: var(--shadow-e2);
}
.lp-role-icon {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-md);
  background: var(--accent-tint);
  color: var(--accent-strong);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.lp-role-card h3 {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--ink);
  margin-bottom: 6px;
}
.lp-role-card p {
  font-size: 0.75rem;
  line-height: 1.6;
  color: var(--ink-soft);
  margin: 0;
}

/* ════════════════════════════════════════════════════════════════════════════
   SECTION — Reports preview
   ════════════════════════════════════════════════════════════════════════════ */
.lp-report-preview {
  background: var(--paper-raised);
  border: 1px solid var(--line);
  border-radius: 10px;
  overflow: hidden;
  max-width: 600px;
  box-shadow: var(--shadow-e1);
}
.lp-report-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid var(--line);
}
.lp-report-title-block h4 {
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--ink);
  margin-bottom: 2px;
}
.lp-report-title-block span {
  font-size: 0.6875rem;
  color: var(--ink-faint);
}
.lp-report-stamp {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 14px;
  border-radius: var(--radius-full);
  background: var(--success-tint);
  color: var(--success);
  font-size: 0.6875rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}
.lp-report-body { padding: 24px; }
.lp-report-meta-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 16px;
  margin-bottom: 20px;
}
.lp-report-meta-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.lp-report-meta-label {
  font-size: 0.5625rem;
  font-weight: 600;
  color: var(--ink-faint);
  text-transform: uppercase;
  letter-spacing: 0.08em;
}
.lp-report-meta-value {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--ink);
  font-variant-numeric: tabular-nums;
}
.lp-report-results {
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  overflow: hidden;
}
.lp-report-result-row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 80px;
  gap: 8px;
  align-items: center;
  padding: 8px 14px;
  font-size: 0.75rem;
  border-bottom: 1px solid var(--line);
}
.lp-report-result-row:last-child { border-bottom: none; }
.lp-report-result-row:first-child {
  background: var(--paper);
  font-weight: 600;
  font-size: 0.625rem;
  color: var(--ink-faint);
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

/* ════════════════════════════════════════════════════════════════════════════
   SECTION — Audit timeline
   ════════════════════════════════════════════════════════════════════════════ */
.lp-audit-timeline {
  position: relative;
  padding-left: 32px;
  margin-top: 40px;
  max-width: 560px;
}
.lp-audit-timeline::before {
  content: '';
  position: absolute;
  top: 0;
  bottom: 0;
  left: 11px;
  width: 2px;
  background: var(--line);
}
.lp-audit-event {
  position: relative;
  padding: 0 0 28px 0;
}
.lp-audit-event:last-child { padding-bottom: 0; }
.lp-audit-dot {
  position: absolute;
  left: -32px;
  top: 2px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid var(--line);
  background: var(--paper-raised);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1;
}
.lp-audit-dot svg { width: 10px; height: 10px; }
.lp-audit-dot.dot-success { border-color: var(--success); color: var(--success); }
.lp-audit-dot.dot-warning { border-color: var(--warning); color: var(--warning); }
.lp-audit-dot.dot-info { border-color: var(--info); color: var(--info); }
.lp-audit-dot.dot-accent { border-color: var(--accent); color: var(--accent); }
.lp-audit-event-title {
  font-weight: 600;
  font-size: 0.8125rem;
  color: var(--ink);
  margin-bottom: 2px;
}
.lp-audit-event-detail {
  font-size: 0.75rem;
  color: var(--ink-soft);
  line-height: 1.5;
}
.lp-audit-event-time {
  font-family: var(--font-mono);
  font-size: 0.625rem;
  color: var(--ink-faint);
  margin-top: 4px;
}

/* ════════════════════════════════════════════════════════════════════════════
   CTA
   ════════════════════════════════════════════════════════════════════════════ */
.lp-cta {
  padding: var(--lp-section) 0;
  text-align: center;
  position: relative;
  overflow: hidden;
}
.lp-cta::after {
  content: '';
  position: absolute;
  top: 0; left: 10%; right: 10%;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--line) 25%, var(--line) 75%, transparent);
}
.lp-cta::before {
  content: '';
  position: absolute;
  top: -100px;
  left: 50%;
  transform: translateX(-50%);
  width: 600px;
  height: 400px;
  background: radial-gradient(ellipse, rgba(166,35,28,0.1) 0%, transparent 70%);
  pointer-events: none;
}
.lp-cta > * { position: relative; z-index: 1; }
.lp-cta h2 {
  font-size: 2.25rem;
  font-weight: 700;
  letter-spacing: -0.03em;
  margin-bottom: 12px;
}
.lp-cta p {
  font-size: 1.0625rem;
  color: var(--ink-soft);
  margin-bottom: 32px;
  max-width: 480px;
  margin-left: auto;
  margin-right: auto;
}
.lp-cta-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
}
.lp-cta-actions .btn { padding: 14px 32px; font-size: 0.9375rem; }

/* ════════════════════════════════════════════════════════════════════════════
   Footer
   ════════════════════════════════════════════════════════════════════════════ */
.lp-footer {
  padding: 40px 0;
  border-top: 1px solid var(--line);
}
.lp-footer-grid {
  display: grid;
  grid-template-columns: 1fr auto auto;
  gap: 64px;
  align-items: start;
}
.lp-footer-brand-block {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.lp-footer-brand {
  display: flex;
  align-items: center;
  gap: 8px;
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 0.9375rem;
  color: var(--ink);
}
.lp-footer-tagline {
  font-size: 0.8125rem;
  color: var(--ink-faint);
  max-width: 280px;
  line-height: 1.5;
}
.lp-footer-col h4 {
  font-size: 0.625rem;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--ink-faint);
  margin-bottom: 14px;
}
.lp-footer-col a {
  display: block;
  font-size: 0.8125rem;
  color: var(--ink-soft);
  padding: 3px 0;
  transition: color var(--duration) var(--ease);
}
.lp-footer-col a:hover { color: var(--accent); }
.lp-footer-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 32px;
  padding-top: 20px;
  border-top: 1px solid var(--line);
  font-size: 0.6875rem;
  color: var(--ink-faint);
}

/* ════════════════════════════════════════════════════════════════════════════
   Responsive
   ════════════════════════════════════════════════════════════════════════════ */
@media (max-width: 1024px) {
  .lp { --lp-gutter: 32px; --lp-section: 80px; }
  .lp-hero { padding: 56px 0 72px; }
  .lp-hero-grid { grid-template-columns: 1fr; gap: 48px; }
  .lp-hero-content { max-width: 100%; }
  .lp-hero h1 { font-size: 2.5rem; }
  .lp-problem-grid { grid-template-columns: 1fr; gap: 16px; }
  .lp-problem-featured { grid-row: auto; }
  .lp-split { grid-template-columns: 1fr; gap: 40px; }
  .lp-split-reverse { direction: ltr; }
  .lp-wf-track { grid-template-columns: repeat(3, 1fr); gap: 12px; }
  .lp-roles-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; }
  .lp-roles-row2 { max-width: 100%; grid-template-columns: repeat(2, 1fr); gap: 14px; }
  .lp-footer-grid { grid-template-columns: 1fr; gap: 32px; }
}

@media (max-width: 768px) {
  .lp { --lp-gutter: 20px; --lp-section: 64px; }
  .lp-nav { padding: 0 20px; height: 56px; }
  .lp-nav-center { display: none; }
  .lp-hero h1 { font-size: 2rem; }
  .lp-hero-desc { font-size: 0.9375rem; }
  .lp-hero-actions { flex-direction: column; }
  .lp-hero-actions .btn { width: 100%; text-align: center; }
  .lp-hero-meta { gap: 24px; }
  .lp-inst-stats { grid-template-columns: repeat(2, 1fr); }
  .lp-inst-param { grid-template-columns: 1.5fr 1fr 1fr auto; }
  .lp-inst-param > *:nth-child(3) { display: none; }
  .lp-section h2 { font-size: 1.625rem; }
  .lp-wf-track { grid-template-columns: repeat(2, 1fr); gap: 10px; }
  .lp-wf-step { padding: 24px 16px 20px; }
  .lp-wf-icon { width: 44px; height: 44px; border-radius: 11px; }
  .lp-roles-grid { grid-template-columns: 1fr; gap: 12px; }
  .lp-roles-row2 { grid-template-columns: 1fr; max-width: 100%; }
  .lp-report-meta-grid { grid-template-columns: 1fr 1fr; }
  .lp-cta h2 { font-size: 1.75rem; }
  .lp-cta-actions { flex-direction: column; align-items: center; }
  .lp-cta-actions .btn { width: 240px; }
  .lp-footer-bottom { flex-direction: column; gap: 8px; text-align: center; }
}

@media (max-width: 480px) {
  .lp-hero h1 { font-size: 1.75rem; }
  .lp-hero-meta { flex-direction: column; gap: 12px; }
  .lp-wf-track { grid-template-columns: 1fr 1fr; gap: 8px; }
  .lp-roles-grid { grid-template-columns: 1fr; gap: 10px; }
  .lp-roles-row2 { grid-template-columns: 1fr; max-width: 100%; gap: 10px; }
  .lp-report-result-row { grid-template-columns: 1fr 1fr auto; }
  .lp-report-result-row > *:nth-child(3) { display: none; }
}
</style>
</head>
<body class="app-public lp">

<!-- ═══════════════════════════════════════════════════════════════════════════
     NAV
     ═══════════════════════════════════════════════════════════════════════════ -->
<nav class="lp-nav">
  <a href="<?= url('') ?>" class="lp-brand">
    <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
      <rect width="28" height="28" rx="7" fill="var(--accent)"/>
      <rect x="11" y="6" width="6" height="1.5" rx=".75" fill="var(--ink-on-accent)" opacity=".92"/><path d="M12.5 7.5h3V12l3 5.5a1 1 0 01-.87 1.5H10.37a1 1 0 01-.87-1.5L12.5 12V7.5z" fill="var(--ink-on-accent)" opacity=".92"/>
    </svg>
    <?= e(APP_NAME) ?>
  </a>
  <div class="lp-nav-center">
    <a href="#capabilities" class="lp-nav-link">Platform</a>
    <a href="#workflow" class="lp-nav-link">Workflow</a>
    <a href="#roles" class="lp-nav-link">Roles</a>
    <a href="<?= url('pages/security.php') ?>" class="lp-nav-link">Security</a>
  </div>
  <div class="lp-nav-actions">
    <a href="<?= url('auth/signup.php') ?>" class="lp-nav-link">Create Workspace</a>
    <a href="<?= url('auth/login.php') ?>" class="btn btn-primary">Sign In</a>
  </div>
</nav>

<main>

<!-- ═══════════════════════════════════════════════════════════════════════════
     01 — HERO
     ═══════════════════════════════════════════════════════════════════════════ -->
<section class="lp-hero">
  <div class="lp-wrap">
    <div class="lp-hero-grid">

      <div class="lp-hero-content">
        <div class="lp-eyebrow"><span class="lp-eyebrow-dot"></span>Laboratory Operations Platform</div>
        <h1>Laboratory testing, <span>automated.</span></h1>
        <p class="lp-hero-desc">Register products, record precision measurements against configurable tolerance bands, manage structured testing workflows, and generate compliance reports — from one platform built for engineering laboratories.</p>
        <div class="lp-hero-actions">
          <a href="<?= url('auth/login.php') ?>" class="btn btn-primary">Sign In to Your Workspace</a>
          <a href="<?= url('auth/signup.php') ?>" class="btn btn-secondary">Create Workspace</a>
        </div>
        <div class="lp-hero-meta">
          <div class="lp-hero-meta-item">
            <span class="lp-hero-meta-value">5</span>
            <span class="lp-hero-meta-label">User Roles</span>
          </div>
          <div class="lp-hero-meta-item">
            <span class="lp-hero-meta-value">6-Step</span>
            <span class="lp-hero-meta-label">Workflow</span>
          </div>
          <div class="lp-hero-meta-item">
            <span class="lp-hero-meta-value">100%</span>
            <span class="lp-hero-meta-label">Audit Trail</span>
          </div>
        </div>
      </div>

      <div class="lp-hero-visual">
        <div class="lp-instrument">
          <div class="lp-inst-header">
            <div class="lp-inst-title">
              <span class="lp-inst-live"></span>
              HV-TR-2407 · High Voltage Withstand Test
            </div>
            <span class="lp-inst-badge">In Testing</span>
          </div>
          <div class="lp-inst-body">
            <div class="lp-inst-stats">
              <div class="lp-inst-stat">
                <div class="lp-inst-stat-label">Sample</div>
                <div class="lp-inst-stat-value">TRF-440</div>
              </div>
              <div class="lp-inst-stat">
                <div class="lp-inst-stat-label">Serial</div>
                <div class="lp-inst-stat-value">FZ-2026-0451</div>
              </div>
              <div class="lp-inst-stat">
                <div class="lp-inst-stat-label">Parameters</div>
                <div class="lp-inst-stat-value">6 / 6</div>
              </div>
              <div class="lp-inst-stat">
                <div class="lp-inst-stat-label">Pass Rate</div>
                <div class="lp-inst-stat-value pass">100%</div>
              </div>
            </div>

            <div class="lp-inst-tolerance">
              <div class="lp-inst-tol-header">
                <span class="lp-inst-tol-title">HV Withstand · Tolerance Band</span>
                <span class="lp-inst-tol-reading">28.4 kV — PASS</span>
              </div>
              <div class="lp-tol-track">
                <div class="lp-tol-band"></div>
                <div class="lp-tol-marker"></div>
              </div>
              <div class="lp-tol-labels">
                <span>0 kV</span>
                <span>MIN 20 kV</span>
                <span>MAX 35 kV</span>
                <span>50 kV</span>
              </div>
            </div>

            <div class="lp-inst-params">
              <div class="lp-inst-param">
                <span>Parameter</span>
                <span>Measured</span>
                <span>Tolerance</span>
                <span>Unit</span>
                <span>Result</span>
              </div>
              <div class="lp-inst-param">
                <span class="lp-param-name">HV Withstand</span>
                <span class="lp-param-val">28.4</span>
                <span class="lp-param-val">20–35</span>
                <span>kV</span>
                <span class="lp-param-pass ok"><span class="lp-param-dot ok"></span>PASS</span>
              </div>
              <div class="lp-inst-param">
                <span class="lp-param-name">Insulation Res.</span>
                <span class="lp-param-val">1520</span>
                <span class="lp-param-val">≥ 1000</span>
                <span>MΩ</span>
                <span class="lp-param-pass ok"><span class="lp-param-dot ok"></span>PASS</span>
              </div>
              <div class="lp-inst-param">
                <span class="lp-param-name">Winding Ratio</span>
                <span class="lp-param-val">0.998</span>
                <span class="lp-param-val">0.99–1.01</span>
                <span>ratio</span>
                <span class="lp-param-pass ok"><span class="lp-param-dot ok"></span>PASS</span>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════════════
     02 — WHY THE PLATFORM EXISTS
     ═══════════════════════════════════════════════════════════════════════════ -->
<section class="lp-section lp-section-alt emerge-section">
  <div class="lp-wrap" data-emerge>
    <span class="lp-section-label" data-emerge-child>The Problem</span>
    <h2 data-emerge-child>Testing laboratories still run on spreadsheets and paper trails.</h2>
    <p class="lp-section-sub" data-emerge-child>Manual records, disconnected workflows, and zero traceability create delays, errors, and compliance gaps that cost laboratories time and credibility.</p>

    <div class="lp-problem-grid" data-emerge-child>
      <div class="lp-problem-card lp-problem-featured">
        <div class="lp-problem-num">01</div>
        <h3>No single source of truth</h3>
        <p>Test results live in spreadsheets, email threads, and paper notebooks. Finding a specific measurement from six months ago means searching through folders and hoping someone filed it correctly. Engineers waste hours assembling evidence from disconnected sources.</p>
      </div>
      <div class="lp-problem-card">
        <div class="lp-problem-num">02</div>
        <h3>Manual compliance overhead</h3>
        <p>Every audit requires assembling evidence from multiple systems. Proving who tested what, when, and whether tolerances were met becomes a full-time job.</p>
      </div>
      <div class="lp-problem-card">
        <div class="lp-problem-num">03</div>
        <h3>Invisible workflow bottlenecks</h3>
        <p>Without a system tracking each product through registration, testing, review, and approval, managers can't see where work is stuck.</p>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════════════
     03 — PRODUCT REGISTRATION
     ═══════════════════════════════════════════════════════════════════════════ -->
<section class="lp-section emerge-section" id="capabilities">
  <div class="lp-wrap" data-emerge>
    <div class="lp-split">
      <div class="lp-split-text" data-emerge-child>
        <span class="lp-section-label">Product Registration</span>
        <h3>Every product enters the system with a complete identity.</h3>
        <p>Register products with serial numbers, batch codes, model specifications, and department assignments. Each product gets a unique lifecycle that tracks it from intake through final certification.</p>
        <div class="lp-split-features">
          <div class="lp-split-feature">
            <svg class="lp-split-feature-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="4,10 8,14 16,6"/></svg>
            <span>Serial number and batch tracking</span>
          </div>
          <div class="lp-split-feature">
            <svg class="lp-split-feature-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="4,10 8,14 16,6"/></svg>
            <span>Department and model classification</span>
          </div>
          <div class="lp-split-feature">
            <svg class="lp-split-feature-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="4,10 8,14 16,6"/></svg>
            <span>Full product lifecycle visibility</span>
          </div>
        </div>
      </div>
      <div class="lp-visual-panel" data-emerge-child>
        <div class="lp-visual-panel-header">
          <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="8" y1="3" x2="8" y2="13"/><line x1="3" y1="8" x2="13" y2="8"/></svg>
          Register New Product
        </div>
        <div class="lp-visual-panel-body">
          <div class="lp-reg-fields">
            <div class="lp-reg-row">
              <div class="lp-reg-field">
                <span class="lp-reg-label">Serial Number</span>
                <div class="lp-reg-input">FZ-2026-0451</div>
              </div>
              <div class="lp-reg-field">
                <span class="lp-reg-label">Spec Model</span>
                <div class="lp-reg-input">TRF-440-HV</div>
              </div>
            </div>
            <div class="lp-reg-row">
              <div class="lp-reg-field">
                <span class="lp-reg-label">Department</span>
                <div class="lp-reg-input">High Voltage Lab</div>
              </div>
              <div class="lp-reg-field">
                <span class="lp-reg-label">Batch Code</span>
                <div class="lp-reg-input">B-2026-Q3-017</div>
              </div>
            </div>
            <div class="lp-reg-field">
              <span class="lp-reg-label">Description</span>
              <div class="lp-reg-input" style="min-height:48px;">440kV Power Transformer — Oil-immersed, three-phase, ONAN/ONAF cooling</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════════════
     04 — PRECISION TESTING
     ═══════════════════════════════════════════════════════════════════════════ -->
<section class="lp-section lp-section-alt emerge-section">
  <div class="lp-wrap" data-emerge>
    <div class="lp-split lp-split-reverse">
      <div class="lp-split-text" data-emerge-child>
        <span class="lp-section-label">Precision Testing</span>
        <h3>Measurements validated against configurable tolerance bands.</h3>
        <p>Define minimum and maximum acceptable values for each test parameter. When a technician records a measurement, the system instantly evaluates it — pass, fail, or marginal — with no manual calculation.</p>
        <div class="lp-split-features">
          <div class="lp-split-feature">
            <svg class="lp-split-feature-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="4,10 8,14 16,6"/></svg>
            <span>Configurable min/max per parameter</span>
          </div>
          <div class="lp-split-feature">
            <svg class="lp-split-feature-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="4,10 8,14 16,6"/></svg>
            <span>Instant pass/fail determination</span>
          </div>
          <div class="lp-split-feature">
            <svg class="lp-split-feature-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="4,10 8,14 16,6"/></svg>
            <span>Out-of-spec flagging and rework tracking</span>
          </div>
        </div>
      </div>
      <div class="lp-visual-panel" data-emerge-child>
        <div class="lp-visual-panel-header">
          <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M8 2v3M8 11v3M2 8h3M11 8h3"/><circle cx="8" cy="8" r="3.5"/></svg>
          Measurement Results — Insulation Resistance Test
        </div>
        <div class="lp-tol-visual">
          <?php
          $params = [
            ['HV Withstand', '28.4 kV', 82, 'ok'],
            ['Insulation Resistance', '1,520 MΩ', 95, 'ok'],
            ['Winding Ratio', '0.998', 90, 'ok'],
            ['No-Load Loss', '12.8 kW', 65, 'warn'],
            ['Short-Circuit Impedance', '11.2%', 30, 'fail'],
          ];
          foreach ($params as $p): ?>
          <div class="lp-tol-param-row">
            <span class="lp-tol-param-name"><?= $p[0] ?></span>
            <div class="lp-tol-param-bar">
              <div class="lp-tol-param-fill <?= $p[3] ?>" style="width:<?= $p[2] ?>%"></div>
            </div>
            <span class="lp-tol-param-val"><?= $p[1] ?></span>
            <span class="lp-tol-param-status">
              <?php if ($p[3] === 'ok'): ?>
                <span class="status-badge badge-success" style="font-size:0.5625rem;padding:2px 8px;"><span class="status-dot"></span>Pass</span>
              <?php elseif ($p[3] === 'warn'): ?>
                <span class="status-badge badge-warning" style="font-size:0.5625rem;padding:2px 8px;"><span class="status-dot"></span>Marginal</span>
              <?php else: ?>
                <span class="status-badge badge-fail" style="font-size:0.5625rem;padding:2px 8px;"><span class="status-dot"></span>Fail</span>
              <?php endif; ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════════════
     05 — WORKFLOW AUTOMATION
     ═══════════════════════════════════════════════════════════════════════════ -->
<section class="lp-section lp-section-dark emerge-section" id="workflow">
  <div class="lp-wrap" data-emerge>
    <div style="text-align:center; max-width:600px; margin:0 auto;" data-emerge-child>
      <span class="lp-section-label">How It Works</span>
      <h2 style="color:rgba(255,255,255,0.92);">From intake to certification<br>in six structured steps.</h2>
      <p class="lp-section-sub" style="color:rgba(255,255,255,0.5); margin-left:auto; margin-right:auto;">Every product follows the same path. No step is skipped, no handoff is lost, and every decision is recorded in an immutable audit trail.</p>
    </div>

    <div class="lp-wf-track">
      <?php
      $steps = [
        ['Register',  'Product enters with serial number, model, and batch identity',
         '<rect x="5" y="1.5" width="10" height="15" rx="1.5"/><path d="M7.5 1v2.5h5V1"/><line x1="7.5" y1="7" x2="12.5" y2="7"/><line x1="7.5" y1="9.5" x2="11" y2="9.5"/><line x1="7.5" y1="12" x2="12.5" y2="12"/>'],
        ['Assign',    'Engineer assigns test type and lab technician',
         '<circle cx="7.5" cy="5.5" r="2.5"/><path d="M3 15c0-3.2 2-5.5 4.5-5.5s4.5 2.3 4.5 5.5"/><path d="M14 7l3 3-3 3"/><line x1="13" y1="10" x2="17" y2="10"/>'],
        ['Test',      'Precision measurements recorded against tolerance bands',
         '<path d="M7.5 2v5L4 13a1.5 1.5 0 001.3 2.2h9.4a1.5 1.5 0 001.3-2.2L12.5 7V2"/><line x1="6" y1="2" x2="14" y2="2"/><circle cx="8.5" cy="12" r="1" fill="currentColor"/><circle cx="11" cy="10.5" r="0.7" fill="currentColor"/>'],
        ['Review',    'Engineer validates results against specifications',
         '<circle cx="8.5" cy="8.5" r="5"/><line x1="12.5" y1="12.5" x2="17" y2="17"/><line x1="6.5" y1="7.5" x2="10.5" y2="7.5"/><line x1="6.5" y1="9.5" x2="9.5" y2="9.5"/>'],
        ['Approve',   'Quality manager certifies compliance',
         '<path d="M10 2l6.5 2.5v5c0 4-2.8 7-6.5 8.5C6.3 16.5 3.5 13.5 3.5 9.5v-5L10 2z"/><polyline points="7 10 9 12 13 8"/>'],
        ['Report',    'Compliance certificate and audit trail generated',
         '<path d="M5 2h7l4 4v11a1.5 1.5 0 01-1.5 1.5h-9A1.5 1.5 0 014 17V3.5A1.5 1.5 0 015 2z"/><path d="M12 2v4h4"/><line x1="7" y1="10" x2="13" y2="10"/><line x1="7" y1="12.5" x2="11" y2="12.5"/><line x1="7" y1="15" x2="13" y2="15"/>'],
      ];
      foreach ($steps as $i => $s): ?>
      <div class="lp-wf-step" data-emerge-child>
        <div class="lp-wf-icon">
          <svg width="24" height="24" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><?= $s[2] ?></svg>
        </div>
        <span class="lp-wf-num">Step <?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
        <span class="lp-wf-name"><?= $s[0] ?></span>
        <span class="lp-wf-desc"><?= $s[1] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════════════
     06 — ROLE-BASED OPERATIONS
     ═══════════════════════════════════════════════════════════════════════════ -->
<section class="lp-section emerge-section" id="roles">
  <div class="lp-wrap" data-emerge>
    <div style="text-align:center; max-width:560px; margin:0 auto;" data-emerge-child>
      <span class="lp-section-label">Role-Based Access</span>
      <h2>Five roles. Clear boundaries.</h2>
      <p class="lp-section-sub" style="margin-left:auto; margin-right:auto;">Each role sees only what it needs. Permissions are enforced at the server level — not just hidden in the UI.</p>
    </div>

    <?php
    $roles = [
      ['Administrator', 'Full system control — user management, workspace settings, department configuration.', '<circle cx="10" cy="6" r="3"/><path d="M3 18c0-4 3-7 7-7s7 3 7 7"/><path d="M15 3l1.5 1.5L15 6M17 3l-1.5 1.5"/>'],
      ['Testing Engineer', 'Register products, create and assign tests, review submitted measurement results.', '<path d="M4 2h8l4 4v10a2 2 0 01-2 2H4a2 2 0 01-2-2V4a2 2 0 012-2z"/><path d="M12 2v4h4"/><path d="M7 12l2 2 4-4"/>'],
      ['Lab Technician', 'Perform tests, record precision measurements, upload supporting documents.', '<path d="M7 2v4L4 11.5a1.2 1.2 0 001 1.7h10a1.2 1.2 0 001-1.7L13 6V2"/><line x1="5" y1="2" x2="15" y2="2"/><circle cx="8" cy="11" r="1" fill="currentColor"/>'],
      ['Quality Manager', 'Review test results, approve or reject, generate quality and compliance reports.', '<path d="M10 2.5l6 2.3v4.4c0 4-2.6 6.6-6 8-3.4-1.4-6-4-6-8V4.8l6-2.3z"/>'],
      ['Auditor', 'Read-only access to all records, audit trails, and exports. Cannot modify any data.', '<circle cx="10" cy="10" r="7"/><path d="M10 6v4l2.5 1.5"/>'],
    ];
    ?>
    <div class="lp-roles-grid">
      <?php for ($i = 0; $i < 3; $i++): ?>
      <div class="lp-role-card" data-emerge-child>
        <div class="lp-role-icon">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><?= $roles[$i][2] ?></svg>
        </div>
        <div>
          <h3><?= $roles[$i][0] ?></h3>
          <p><?= $roles[$i][1] ?></p>
        </div>
      </div>
      <?php endfor; ?>
    </div>
    <div class="lp-roles-row2">
      <?php for ($i = 3; $i < 5; $i++): ?>
      <div class="lp-role-card" data-emerge-child>
        <div class="lp-role-icon">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><?= $roles[$i][2] ?></svg>
        </div>
        <div>
          <h3><?= $roles[$i][0] ?></h3>
          <p><?= $roles[$i][1] ?></p>
        </div>
      </div>
      <?php endfor; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════════════
     07 — REPORTS & COMPLIANCE
     ═══════════════════════════════════════════════════════════════════════════ -->
<section class="lp-section lp-section-alt emerge-section">
  <div class="lp-wrap" data-emerge>
    <div class="lp-split">
      <div class="lp-split-text" data-emerge-child>
        <span class="lp-section-label">Reports & Compliance</span>
        <h3>Structured reports that auditors actually trust.</h3>
        <p>Generate compliance reports with full measurement data, approval chains, and certification status. Every report links back to the original test records, reviewer decisions, and timestamps — no manual assembly required.</p>
        <div class="lp-split-features">
          <div class="lp-split-feature">
            <svg class="lp-split-feature-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="4,10 8,14 16,6"/></svg>
            <span>PDF and CSV export</span>
          </div>
          <div class="lp-split-feature">
            <svg class="lp-split-feature-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="4,10 8,14 16,6"/></svg>
            <span>Full approval chain included</span>
          </div>
          <div class="lp-split-feature">
            <svg class="lp-split-feature-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="4,10 8,14 16,6"/></svg>
            <span>Cycle time and yield analytics</span>
          </div>
        </div>
      </div>

      <div class="lp-report-preview" data-emerge-child>
        <div class="lp-report-header">
          <div class="lp-report-title-block">
            <h4>CPRI Test Certificate</h4>
            <span>TRF-440-HV · FZ-2026-0451</span>
          </div>
          <div class="lp-report-stamp">
            <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="4,8 7,11 12,5"/></svg>
            Approved
          </div>
        </div>
        <div class="lp-report-body">
          <div class="lp-report-meta-grid">
            <div class="lp-report-meta-item">
              <span class="lp-report-meta-label">Test Date</span>
              <span class="lp-report-meta-value">Aug 12, 2026</span>
            </div>
            <div class="lp-report-meta-item">
              <span class="lp-report-meta-label">Reviewed By</span>
              <span class="lp-report-meta-value">A. Rahman</span>
            </div>
            <div class="lp-report-meta-item">
              <span class="lp-report-meta-label">Pass Rate</span>
              <span class="lp-report-meta-value" style="color:var(--success);">100%</span>
            </div>
          </div>
          <div class="lp-report-results">
            <div class="lp-report-result-row">
              <span>Parameter</span>
              <span>Measured</span>
              <span>Tolerance</span>
              <span>Result</span>
            </div>
            <div class="lp-report-result-row">
              <span style="font-weight:500;color:var(--ink);">HV Withstand</span>
              <span style="font-family:var(--font-mono);">28.4 kV</span>
              <span style="color:var(--ink-faint);">20–35 kV</span>
              <span class="status-badge badge-success" style="font-size:0.5625rem;padding:2px 8px;"><span class="status-dot"></span>Pass</span>
            </div>
            <div class="lp-report-result-row">
              <span style="font-weight:500;color:var(--ink);">Insulation Res.</span>
              <span style="font-family:var(--font-mono);">1,520 MΩ</span>
              <span style="color:var(--ink-faint);">≥ 1,000 MΩ</span>
              <span class="status-badge badge-success" style="font-size:0.5625rem;padding:2px 8px;"><span class="status-dot"></span>Pass</span>
            </div>
            <div class="lp-report-result-row">
              <span style="font-weight:500;color:var(--ink);">Winding Ratio</span>
              <span style="font-family:var(--font-mono);">0.998</span>
              <span style="color:var(--ink-faint);">0.99–1.01</span>
              <span class="status-badge badge-success" style="font-size:0.5625rem;padding:2px 8px;"><span class="status-dot"></span>Pass</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════════════
     08 — AUDIT & SECURITY
     ═══════════════════════════════════════════════════════════════════════════ -->
<section class="lp-section">
  <div class="lp-wrap">
    <div class="lp-split">
      <div class="lp-split-text">
        <span class="lp-section-label reveal">Audit & Security</span>
        <h3 class="reveal">Every action is recorded. Nothing is deleted.</h3>
        <p class="reveal">The audit log is append-only — records cannot be edited or removed. Every login, measurement, review decision, and status change is timestamped and attributed to a specific user. Workspace isolation ensures complete data separation between organizations.</p>
        <div class="lp-split-features reveal">
          <div class="lp-split-feature">
            <svg class="lp-split-feature-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="4,10 8,14 16,6"/></svg>
            <span>Append-only audit log</span>
          </div>
          <div class="lp-split-feature">
            <svg class="lp-split-feature-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="4,10 8,14 16,6"/></svg>
            <span>CSRF protection and bcrypt hashing</span>
          </div>
          <div class="lp-split-feature">
            <svg class="lp-split-feature-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="4,10 8,14 16,6"/></svg>
            <span>Complete workspace isolation</span>
          </div>
        </div>
      </div>

      <div class="reveal">
        <div class="lp-audit-timeline">
          <div class="lp-audit-event">
            <div class="lp-audit-dot dot-success"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3,6 5,8 9,4"/></svg></div>
            <div class="lp-audit-event-title">Test approved — TRF-440-HV</div>
            <div class="lp-audit-event-detail">Quality Manager A. Rahman approved insulation resistance test results. All 6 parameters within tolerance.</div>
            <div class="lp-audit-event-time">2026-08-12 · 14:32:07 UTC</div>
          </div>
          <div class="lp-audit-event">
            <div class="lp-audit-dot dot-warning"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 3v3"/><circle cx="6" cy="8.5" r="0.5" fill="currentColor"/></svg></div>
            <div class="lp-audit-event-title">Measurements submitted for review</div>
            <div class="lp-audit-event-detail">Lab Technician M. Hassan submitted measurements for FZ-2026-0451. 6 parameters recorded.</div>
            <div class="lp-audit-event-time">2026-08-12 · 11:18:42 UTC</div>
          </div>
          <div class="lp-audit-event">
            <div class="lp-audit-dot dot-info"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="6" cy="6" r="4"/><path d="M6 4v2.5l1.5 1"/></svg></div>
            <div class="lp-audit-event-title">Testing started</div>
            <div class="lp-audit-event-detail">Lab Technician M. Hassan began insulation resistance testing on product FZ-2026-0451.</div>
            <div class="lp-audit-event-time">2026-08-12 · 09:05:11 UTC</div>
          </div>
          <div class="lp-audit-event">
            <div class="lp-audit-dot dot-accent"><svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="6" cy="4" r="2"/><path d="M2 11c0-2.2 1.8-4 4-4s4 1.8 4 4"/></svg></div>
            <div class="lp-audit-event-title">Test assigned to M. Hassan</div>
            <div class="lp-audit-event-detail">Testing Engineer S. Khan assigned insulation resistance test. Due: Aug 14, 2026.</div>
            <div class="lp-audit-event-time">2026-08-11 · 16:44:29 UTC</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════════════
     09 — CTA
     ═══════════════════════════════════════════════════════════════════════════ -->
<section class="lp-cta">
  <div class="lp-wrap">
    <h2 class="reveal">Ready to automate your laboratory?</h2>
    <p class="reveal">Sign in to your workspace or create a new one. Setup takes under five minutes.</p>
    <div class="lp-cta-actions reveal">
      <a href="<?= url('auth/login.php') ?>" class="btn btn-primary">Sign In</a>
      <a href="<?= url('auth/signup.php') ?>" class="btn btn-secondary">Create Workspace</a>
    </div>
  </div>
</section>

</main>

<!-- ═══════════════════════════════════════════════════════════════════════════
     FOOTER
     ═══════════════════════════════════════════════════════════════════════════ -->
<footer class="lp-footer">
  <div class="lp-wrap">
    <div class="lp-footer-grid">
      <div class="lp-footer-brand-block">
        <div class="lp-footer-brand">
          <svg width="22" height="22" viewBox="0 0 28 28" fill="none">
            <rect width="28" height="28" rx="7" fill="var(--accent)"/>
            <rect x="11" y="6" width="6" height="1.5" rx=".75" fill="var(--ink-on-accent)" opacity=".92"/><path d="M12.5 7.5h3V12l3 5.5a1 1 0 01-.87 1.5H10.37a1 1 0 01-.87-1.5L12.5 12V7.5z" fill="var(--ink-on-accent)" opacity=".92"/>
          </svg>
          FZ Engineering
        </div>
        <p class="lp-footer-tagline">Laboratory testing automation platform for engineering organizations that require precision, traceability, and compliance.</p>
      </div>
      <div class="lp-footer-col">
        <h4>Platform</h4>
        <a href="<?= url('auth/login.php') ?>">Sign In</a>
        <a href="<?= url('auth/signup.php') ?>">Create Workspace</a>
        <a href="<?= url('guide/index.php') ?>">User Guide</a>
      </div>
      <div class="lp-footer-col">
        <h4>Company</h4>
        <a href="<?= url('pages/privacy.php') ?>">Privacy Policy</a>
        <a href="<?= url('pages/terms.php') ?>">Terms of Service</a>
        <a href="<?= url('pages/contact.php') ?>">Contact</a>
        <a href="<?= url('pages/security.php') ?>">Security</a>
      </div>
    </div>
    <div class="lp-footer-bottom">
      <span>&copy; 2026 FZ Engineering · v<?= APP_VERSION ?></span>
      <span>Laboratory Automation System</span>
    </div>
  </div>
</footer>

<script src="<?= asset('js/app.js') ?>"></script>
<script>
(function(){
  document.querySelectorAll('.lp-nav-link[href^="#"]').forEach(function(a) {
    a.addEventListener('click', function(e) {
      var id = a.getAttribute('href').substring(1);
      var el = document.getElementById(id);
      if (el) {
        e.preventDefault();
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
})();
</script>
</body>
</html>
