# CricIntel AI

## Intelligent Cricket Team Management, Match Analytics & Tactical Decision Platform

CricIntel AI is a full-stack cricket operations, analytics, decision-support, and responsible-AI platform built to manage the complete cricket workflow from organization administration and player management to match scoring, selection, training, scouting, tactical planning, predictive analytics, grounded AI, natural-language analytics, realtime notifications, and professional reporting.

The system is implemented through **Milestones P1–P18** and is designed as a serious full-stack engineering project rather than a thin AI demo.

> **Architecture policy:** CricIntel AI is developed without Docker. Local development uses native Windows/Linux services, PostgreSQL, Laravel, React/Vite, Laravel Reverb, and a Python virtual environment for the AI service.

---

# Table of Contents

1. Project Overview
2. Why CricIntel AI
3. Problems Solved
4. Core Users and Roles
5. Technology Stack
6. High-Level Architecture
7. P1–P18 Feature Summary
8. P1 — Authentication & RBAC
9. P2 — Organization / Club / Team / Season
10. P3 — Player Management
11. P4 — Venues / Tournaments / Fixtures
12. P5 — Match Scoring Engine
13. P6 — Statistics Engine
14. P7 — Analytics Dashboard
15. P8 — Realtime Match Centre
16. P9 — Squad Selection & Playing XI
17. P10 — Training & Player Development
18. P11 — Scouting & Recruitment
19. P12 — Opponent Intelligence
20. P13 — Tactical Planning & Collaboration
21. P14 — Predictive Analytics
22. P15 — Grounded AI Strategy Assistant
23. P16 — Controlled Natural-Language Analytics
24. P17 — Notifications
25. P18 — Professional Reporting
26. Security Architecture
27. Responsible AI Design
28. Database & Data Flow
29. Realtime Architecture
30. Queue Architecture
31. Reporting Architecture
32. Main API Areas
33. Frontend Routes
34. Local Development Setup
35. Running the System
36. Testing
37. Recommended Demo Data
38. End-to-End Demo Workflow
39. Project Strengths
40. Real-World Industry Value
41. Portfolio Description
42. CV Description
43. Future Improvements
44. Current Status

---

# 1. Project Overview

CricIntel AI connects cricket administration, operations, performance analysis, tactical planning, and AI-assisted decision support inside one integrated platform.

The platform answers practical questions such as:

- Which players are currently available?
- Who should be considered for the match squad?
- What is the best Playing XI structure?
- Which batters perform best in different phases?
- Which bowlers are strongest at the death?
- How does an opponent perform during the powerplay?
- Which bowler is more effective against left-handed batters?
- How has player form changed recently?
- What happened in the previous four matches?
- What tactical threats should coaches prepare for?
- Which scouting prospects deserve further attention?
- What training objectives should be monitored?
- Can an AI recommendation be supported by real cricket evidence?
- Can coaches ask cricket questions in natural language without allowing arbitrary SQL?
- Can professional reports be exported for coaches, selectors, management, and analysts?

CricIntel AI is designed around a single principle:

> **Operational decisions should be connected to trustworthy cricket data, controlled analytics, clear permissions, and explainable evidence.**

---

# 2. Why CricIntel AI

Many cricket clubs, academies, domestic teams, and performance groups manage information across disconnected systems:

- spreadsheets;
- paper records;
- messaging groups;
- standalone scorecards;
- training sheets;
- selection notes;
- scouting documents;
- coach observations;
- manually created reports.

This creates several problems:

- fragmented information;
- duplicated work;
- poor traceability;
- weak access control;
- inconsistent analysis;
- slow tactical preparation;
- difficulty connecting match performance to training and selection;
- unsafe use of generative AI;
- limited realtime collaboration.

CricIntel AI centralizes these workflows while preserving clear domain boundaries.

---

# 3. Problems Solved

## Fragmented Cricket Operations

CricIntel brings organization, team, player, competition, match, training, scouting, and tactical information into one system.

## Manual Selection Processes

Squad and Playing XI workflows use stored player availability and structured selection decisions.

## Score Data Disconnected from Decisions

Delivery-level match data feeds statistics, analytics, opponent intelligence, predictions, AI evidence, and reports.

## Weak Opponent Preparation

CricIntel provides phase, matchup, handedness, bowling-style, partnership, and opponent threat analytics.

## Unstructured Player Development

Training sessions, drills, attendance, fitness tests, assessments, objectives, and development plans are stored together.

## Unstructured Recruitment

Scouting profiles, reports, ratings, recommendations, comparison, and recruitment status workflows are integrated.

## Tactical Knowledge Scattered Across Messages

Strategy plans support sections, notes, comments, mentions, assignments, attachments, and versioning.

## AI Hallucination Risk

The P15 strategy assistant is grounded in structured evidence and rejects unsupported claims.

## Unsafe NL-to-SQL Designs

P16 never lets an LLM directly execute arbitrary SQL. Natural-language requests map to controlled analytics intents and validated filters.

## Slow Operational Communication

P17 adds queued database, email, and realtime notifications.

## Manual Reporting

P18 produces professional PDF, Excel, and CSV reports with stored export history.

---

# 4. Core Users and Roles

CricIntel supports multiple roles.

## Administrator

Full platform access.

Typical responsibilities:

- organization administration;
- user/role management;
- system-wide oversight;
- unrestricted organization workflows.

## Coach

Typical access:

- training;
- tactical planning;
- performance review;
- squad support;
- analytics;
- reporting.

## Analyst

Typical access:

- analytics;
- opponent intelligence;
- natural-language analytics;
- tactical collaboration;
- reporting.

## Selector

Typical access:

- squad selection;
- Playing XI;
- scouting;
- recruitment;
- analytics;
- reporting.

## Team Manager

Typical access:

- operational workflows;
- team management;
- player availability;
- squad support;
- training support;
- reporting.

## Player

Restricted player-facing access depending on configured policies.

---

# 5. Technology Stack

## Backend

- PHP 8.3+
- Laravel 12
- Laravel Sanctum
- Laravel validation
- Gates / policies / RBAC
- Laravel queues
- Laravel notifications
- Laravel Reverb
- Eloquent ORM
- Query Builder
- REST API architecture

## Frontend

- React
- TypeScript
- Vite
- React Router
- TanStack Query
- Recharts
- Laravel Echo
- Responsive CSS

## Database

- PostgreSQL 18
- Relational cricket-domain model
- JSONB where appropriate
- Migration-driven schema

## Realtime

- Laravel Reverb
- Laravel Echo
- Private match channels
- Private user notification channels

## AI / Machine Learning

- Python 3.11
- FastAPI
- scikit-learn
- Ridge Regression baseline
- Random Forest candidate model
- structured model metadata
- chronological evaluation
- residual-based uncertainty intervals

## Reporting

- Barryvdh Laravel DomPDF
- Blade report templates
- PhpSpreadsheet
- CSV export
- Laravel storage
- queued report generation

## Development Environment

- Windows PowerShell
- native PostgreSQL
- native Laravel/PHP
- Vite development server
- Python virtual environment
- **No Docker**

---

# 6. High-Level Architecture

```text
                           ┌─────────────────────────┐
                           │       React + TS        │
                           │      Vite Frontend      │
                           │       :5173             │
                           └────────────┬────────────┘
                                        │
                              REST / Sanctum / Echo
                                        │
                           ┌────────────▼────────────┐
                           │       Laravel 12        │
                           │        REST API         │
                           │         :8000           │
                           └───────┬────────┬────────┘
                                   │        │
                                   │        ├──────────────┐
                                   │        │              │
                         ┌─────────▼───┐ ┌──▼─────────┐ ┌──▼─────────────┐
                         │ PostgreSQL  │ │  Reverb    │ │    Queues      │
                         │    :5433    │ │ Realtime   │ │ Reports /      │
                         │             │ │            │ │ Notifications  │
                         └─────────────┘ └────────────┘ └────────────────┘
                                   │
                                   │ structured ML / AI requests
                                   ▼
                         ┌─────────────────────┐
                         │ Python FastAPI      │
                         │ AI / ML Service     │
                         │ :8100               │
                         └─────────────────────┘
```

---

# 7. P1–P18 Feature Summary

| Milestone | Main Capability |
|---|---|
| P1 | Authentication & RBAC |
| P2 | Organization → Club → Team → Season |
| P3 | Player Management |
| P4 | Venues, Tournaments & Fixtures |
| P5 | Match / Innings / Over / Delivery Scoring |
| P6 | Cricket Statistics |
| P7 | Analytics Dashboard |
| P8 | Realtime Match Centre |
| P9 | Squad / Match Squad / Playing XI |
| P10 | Training & Player Development |
| P11 | Scouting & Recruitment |
| P12 | Opponent Intelligence |
| P13 | Tactical Planning & Collaboration |
| P14 | Predictive Analytics |
| P15 | Grounded AI Strategy Assistant |
| P16 | Controlled Natural-Language Analytics |
| P17 | Event-Driven Notifications |
| P18 | Professional Reporting |

---

# 8. P1 — Authentication & RBAC

P1 establishes the security and user foundation.

## Authentication

Implemented:

- register;
- login;
- logout;
- authenticated user lookup;
- Sanctum SPA authentication;
- CSRF-cookie flow;
- session-based authentication.

## Roles

Supported roles:

- Administrator;
- Coach;
- Analyst;
- Selector;
- Team Manager;
- Player.

## Authorization

Authorization is enforced server-side.

The platform supports:

- global Administrator override;
- coaching access;
- analytics access;
- selection access;
- organization-scoped resource authorization.

---

# 9. P2 — Organization / Club / Team / Season

P2 creates the organizational cricket hierarchy.

```text
Organization
   ↓
Club
   ↓
Team
   ↓
Season
```

## Organization Management

Features:

- create;
- view;
- update;
- delete;
- search;
- filtering;
- sorting;
- pagination;
- membership management.

## Club Management

Features:

- organization-scoped club CRUD;
- logo/media validation;
- club search and filtering.

## Team Management

Features:

- club/team relationships;
- team CRUD;
- organization ownership;
- team context used by later modules.

## Season Management

Features:

- season CRUD;
- date/context metadata;
- downstream filtering for analytics and reports.

---

# 10. P3 — Player Management

P3 introduces complete player lifecycle management.

## Player Profiles

Supports:

- player identity;
- display name;
- positions;
- batting style;
- bowling style;
- contact information;
- cricket attributes.

## Team Membership

Supports:

- player-to-team relationship;
- current team membership;
- historical membership structure.

## Player Availability

Availability states can support workflows such as:

- Available;
- Conditional;
- Unavailable;
- Unknown.

These values later influence P9 selection.

## Documents / Media

Player-related documents and profile files can be stored with validated uploads.

---

# 11. P4 — Venues / Tournaments / Fixtures

P4 adds competition management.

## Venues

Features:

- venue CRUD;
- venue metadata;
- venue use in fixtures;
- venue-based analytics/reporting.

## Tournaments

Features:

- tournament CRUD;
- format information;
- team participation;
- season integration.

## Tournament Teams

Teams can be registered into tournaments.

## Fixtures

Features:

- home team;
- away team;
- tournament;
- venue;
- scheduled date/time;
- status;
- round/context;
- updates used by P17 notifications.

---

# 12. P5 — Match Scoring Engine

P5 is the deterministic source-of-truth scoring layer.

Hierarchy:

```text
Match
  ↓
Innings
  ↓
Over
  ↓
Delivery
  ↓
Wicket
```

## Delivery Data

A delivery can capture:

- striker;
- non-striker;
- bowler;
- runs off bat;
- extras;
- total runs;
- wicket;
- wicket type;
- dismissed player;
- delivery state.

## Extras

Scoring logic supports common extras such as:

- wides;
- no-balls;
- other configured extras.

## Wickets

Wicket information supports dismissal types such as:

- bowled;
- caught;
- LBW;
- run-out;
- retired hurt;
- obstructing field.

## Match Completion

The engine supports:

- over progress;
- innings completion;
- match completion;
- downstream realtime updates.

---

# 13. P6 — Statistics Engine

P6 derives cricket statistics from P5 source data.

## Batting Statistics

Examples:

- runs;
- balls;
- strike rate;
- dismissals;
- boundaries;
- phase performance.

## Bowling Statistics

Examples:

- overs / legal balls;
- runs conceded;
- wickets;
- economy;
- wicket rate;
- dot-ball percentage.

## Team Statistics

Examples:

- team runs;
- run rate;
- phase scoring;
- match totals.

## Match Statistics

Match-level statistics are derived from innings and deliveries.

## Phase Statistics

T20 phases:

```text
Powerplay: overs 1–6
Middle:    overs 7–15
Death:     over 16+
```

ODI phases:

```text
Powerplay: overs 1–10
Middle:    overs 11–40
Death:     over 41+
```

## Cache Invalidation

Statistics cache invalidation is connected to changes in:

- Match;
- Innings;
- Delivery;
- Wicket.

---

# 14. P7 — Analytics Dashboard

P7 provides interactive analytics to turn statistics into usable insights.

Frontend technologies include:

- React;
- TypeScript;
- TanStack Query;
- Recharts.

## Analytics Views

Examples:

- player performance;
- team performance;
- match analysis;
- phase trends;
- batting charts;
- bowling charts;
- comparison visualizations.

## Filters

Depending on analytics type:

- team;
- player;
- season;
- date;
- format;
- match;
- phase.

---

# 15. P8 — Realtime Match Centre

P8 adds realtime match updates.

## Realtime Architecture

```text
Match Operator
     ↓
Laravel scoring write
     ↓
Broadcast Event
     ↓
Laravel Reverb
     ↓
Private match.{id}
     ↓
React + Laravel Echo
```

## Realtime Events

Events can include:

- delivery recorded;
- wicket;
- innings completion;
- match completion.

## Private Channel Security

Users can subscribe to:

```text
match.{matchId}
```

only when authorized.

Administrator access is supported.

Other users must belong to the organization that owns the match.

---

# 16. P9 — Squad Selection & Playing XI

P9 implements the selection workflow.

```text
Tournament Squad
      ↓
Match Squad
      ↓
Playing XI
      ↓
Batting Order
      ↓
Bowling Roles
```

## Selection Decisions

Selection decisions can record:

- selection state;
- availability;
- override behavior;
- reason/context.

## Availability Rules

Recommended behavior:

- Available → normal selection;
- Conditional → warning;
- Unavailable → blocked unless authorized override/reason;
- Unknown → warning.

## Match Operator Integration

Where a Playing XI exists, scoring workflows can restrict:

- striker;
- non-striker;
- bowler;

to confirmed players.

---

# 17. P10 — Training & Player Development

P10 extends CricIntel beyond matches.

## Training Sessions

Supports:

- training date;
- team;
- coach;
- session type;
- location;
- duration;
- status.

## Training Drills

Reusable drills can be assigned to sessions.

## Training Participants

Players can be assigned to sessions.

## Attendance

Tracks player participation.

## Fitness Tests

Stores fitness-related assessments.

## Player Assessments

Coach/staff evaluations can be recorded.

## Training Objectives

Development targets can be created.

## Development Plans

Longer-term player development plans can connect objectives and progress.

---

# 18. P11 — Scouting & Recruitment

P11 introduces recruitment workflows.

## Scouting Profiles

Profiles can represent:

- external prospects;
- recruitment candidates;
- monitored players.

## Scouting Reports

Reports can include:

- cricket observations;
- notes;
- strengths;
- weaknesses;
- potential;
- recommendation.

## Ratings

Ratings support structured comparison.

Example scale:

```text
1–10
```

## Recommendations

Supported recommendation styles include:

- Highly Recommend;
- Recommend;
- Monitor;
- Do Not Recommend.

## Workflow Statuses

Examples:

- Watching;
- Shortlisted;
- Recommended;
- Rejected;
- Converted.

## Comparison

Prospects can be compared before recruitment decisions.

---

# 19. P12 — Opponent Intelligence

P12 creates deterministic opposition analysis.

No LLM is required.

## Team Analysis

Analyze opponent trends from recorded match data.

## Batter Analysis

Examples:

- strike rate;
- phase scoring;
- handedness;
- matchup behavior.

## Bowler Analysis

Examples:

- economy;
- wickets;
- wicket rate;
- phase effectiveness.

## Bowling Categories

Supported classification concepts include:

- pace;
- left-arm pace;
- spin;
- off-spin;
- left-arm spin;
- unknown.

## Handedness

Batting hand:

- left;
- right;
- unknown.

## Sample-Size Awareness

Sample categories:

- none;
- very small;
- small;
- adequate.

This prevents overconfidence from tiny samples.

## Wicket Attribution

Bowler-creditable dismissals exclude cases such as:

- run-out;
- retired hurt;
- obstructing field.

## Main P12 Analytics

- opponent team profile;
- batter analysis;
- bowler analysis;
- batter-vs-bowler matchup;
- partnerships;
- phase threats.

---

# 20. P13 — Tactical Planning & Collaboration

P13 provides a structured tactical workspace.

## Strategy Plans

A strategy plan can contain standard sections such as:

- Pitch Notes;
- Weather Notes;
- Available Players;
- Recent Form;
- Playing XI;
- Batting Plan;
- Bowling Plan;
- Matchups;
- Opponent Threats;
- Powerplay Strategy;
- Middle-over Strategy;
- Death-over Strategy.

## Tactical Notes

Detailed tactical notes can be added.

## Comments

Users can collaborate on tactical content.

## Mentions

Users can mention other team members.

## Attachments

Supporting files can be attached where permitted.

## Assignments

Tasks/responsibilities can be assigned.

## Versions

Strategy versions provide historical traceability.

---

# 21. P14 — Predictive Analytics

P14 introduces a local Python ML service.

## Architecture

```text
React :5173
   ↓
Laravel :8000
   ↓
FastAPI :8100
   ↓
PostgreSQL :5433
```

## Predictive Capabilities

Implemented:

- expected batter score range;
- expected bowler economy;
- expected first-innings team total;
- deterministic player form trend.

## ML Training Design

Data split:

```text
70% train
15% validation
15% test
```

Split is chronological and performed by whole match to reduce leakage.

## Models

Baseline:

```text
Ridge Regression
```

Candidate:

```text
RandomForestRegressor
```

Random Forest is selected only when validation performance is no worse than the baseline under configured selection logic.

## Metrics

Evaluation includes:

- MAE;
- RMSE;
- R².

## Uncertainty

Prediction intervals use empirical validation residuals.

Typical target:

```text
80% prediction interval
```

## Leakage Controls

Examples:

- chronological split;
- whole matches remain in one partition;
- completed innings used;
- lagged/shifted historical features;
- first-innings-only team-total training.

## Local Model Versioning

Models are stored locally by:

```text
organization
  └── target
       └── model versions
```

---

# 22. P15 — Grounded AI Strategy Assistant

P15 adds generative AI while keeping evidence control on the Laravel side.

## Architecture

```text
Match ID
   ↓
Laravel StrategyContextBuilder
   ↓
Structured cricket context
   ↓
Evidence + provenance
   ↓
Deterministic recommendations
   ↓
FastAPI strategy adapter
   ↓
Configured LLM provider
   ↓
Structured JSON response
   ↓
Laravel GroundedResponseValidator
   ↓
Accepted / rejected response
```

## Important Principle

The LLM never receives:

- database credentials;
- arbitrary SQL access;
- unrestricted database connectivity.

It receives structured, authorized context.

## Context Sources

Can include:

- match context;
- focus/opponent;
- squad;
- Playing XI;
- batting order;
- bowling assignments;
- availability;
- recent form;
- career batting;
- career bowling;
- phase statistics;
- handedness;
- opponent batter phase data;
- venue data;
- matchups;
- training assessments;
- development context;
- scouting context.

## Evidence IDs

Numeric cricket evidence is assigned provenance IDs such as:

```text
E0001
E0002
E0003
```

## Deterministic Recommendations

Examples:

- death bowling options vs left-handed batters;
- opponent powerplay threat;
- direct batter-bowler matchups;
- Playing XI structural checks.

## Grounding Validation

The validator:

- requires evidence references;
- rejects unknown evidence IDs;
- checks numeric claims against cited evidence;
- rejects unsupported recommendations;
- rejects a response if no valid claims/recommendations survive validation.

## AI Kill Switch

AI can be disabled through configuration.

---

# 23. P16 — Controlled Natural-Language Analytics

P16 lets users ask analytics questions in natural language while explicitly preventing arbitrary SQL.

## Architecture

```text
Natural-language question
        ↓
Prompt Injection Guard
        ↓
Deterministic Intent Parser
        ↓
Controlled Analytics Schema
        ↓
Entity Resolver
        ↓
Validated Filters
        ↓
Whitelisted Analytics Service
        ↓
PostgreSQL
        ↓
Structured Result
        ↓
Deterministic Explanation
        ↓
Table / Chart
```

## Supported Intents

Examples include:

- rank bowlers by phase vs batting hand;
- team run-rate trend;
- batter phase performance;
- bowler phase performance;
- player form trend;
- team phase scoring;
- matchup summary;
- venue scoring summary;
- opponent phase threats.

## Example Questions

```text
Show me our best death-over bowlers against left-handed batters this season.
```

```text
Why did our middle-over run rate decrease during our previous four matches?
```

```text
Who are the opposition main powerplay threats?
```

## Controlled Filters

Supported filters include:

- metric;
- entity;
- team;
- season;
- opponent;
- venue;
- format;
- phase;
- date range;
- player;
- batter;
- bowler;
- limit;
- sort direction.

## Security

P16 does not expose fields such as:

```text
sql
raw_sql
query_text
```

Natural language is never directly interpolated into SQL.

## Prompt Injection Protection

Blocks requests attempting to:

- ignore previous instructions;
- reveal system prompts;
- execute SQL;
- use DROP/DELETE/TRUNCATE/INSERT/UPDATE patterns;
- use UNION SELECT;
- request raw query execution.

---

# 24. P17 — Event-Driven Notifications

P17 adds professional application notifications.

## Notification Events

Implemented events include:

- Squad announced;
- Player selected;
- Player removed;
- Training assigned;
- Training changed;
- Fixture changed;
- Match starting;
- Tactical report ready;
- Player availability changed;
- Player marked injured;
- Analyst mentioned;
- Coach comment added.

## Architecture

```text
Domain change
    ↓
Model observer
    ↓
Laravel domain event
    ↓
Queued listener
    ↓
Recipient resolution
    ↓
Notification preferences
    ↓
Database / Email / Reverb
    ↓
React notification center
```

## Database Notifications

Notification history is stored.

## Email Notifications

Email can be enabled for important operational events.

## Realtime Notifications

Laravel broadcast notifications use the private user channel:

```text
App.Models.User.{id}
```

Only the matching authenticated user can subscribe.

## Notification Preferences

Per-event preferences support:

- database;
- email;
- realtime.

## Notification UI

Features include:

- topbar bell;
- unread count;
- latest notifications;
- notification center;
- mark read;
- mark unread;
- mark all read;
- delete;
- preferences.

## Queue Retry Strategy

Notification jobs use retry/backoff behavior to avoid blocking domain writes.

---

# 25. P18 — Professional Reporting

P18 provides management-ready exports.

## Report Types

Eight report types are supported:

1. Player Performance Report
2. Match Report
3. Team Performance Report
4. Opponent Report
5. Training Report
6. Scouting Report
7. Tournament Report
8. Tactical Preparation Report

## Export Formats

Supported:

- PDF;
- Excel XLSX;
- CSV where appropriate.

## Report Architecture

```text
React Report Center
        ↓
ReportController
        ↓
ReportAccessService
        ↓
ReportDataService
        ↓
ReportDocument
        ↓
ReportExporterFactory
        ↓
PDF / XLSX / CSV exporter
        ↓
Laravel Storage
        ↓
report_exports metadata
```

## PDF

PDF uses:

- reusable Blade template;
- DomPDF;
- structured summary;
- tables;
- lightweight chart representation;
- metadata;
- limitations.

## Excel

XLSX uses PhpSpreadsheet.

Includes:

- report metadata;
- summaries;
- tables;
- chart data sheet.

## CSV

CSV is generated using a lightweight server-side exporter.

## Queued Heavy Exports

PDF and XLSX are queued.

CSV can be generated synchronously.

## Report Metadata

Stored fields include:

- organization;
- requester;
- report type;
- format;
- filters;
- status;
- filename;
- storage path;
- MIME type;
- file size;
- queued time;
- started time;
- completed time;
- failed time;
- error information.

## Predictable Filenames

Example:

```text
CricIntel_PlayerPerformanceReport_KamalPerera_2026-09-01_to_2026-10-06_R000042.pdf
```

## Report UI

The frontend supports:

- report type selection;
- export format;
- entity filters;
- date range;
- phase;
- data preview;
- structured summary;
- preview chart;
- preview table;
- queue status;
- report history;
- download;
- delete.

---

# 26. Security Architecture

Security is enforced primarily by the backend.

## Authentication

Laravel Sanctum SPA authentication provides:

- CSRF-aware session authentication;
- stateful frontend domains;
- authenticated APIs.

## Authorization

Server-side checks include:

- RBAC;
- global Administrator override;
- organization membership;
- feature-specific permissions;
- resource ownership.

## File Uploads

Upload workflows use:

- validation;
- accepted file types;
- file-size restrictions;
- controlled storage.

## SQL Injection Protection

CricIntel uses:

- Eloquent;
- Laravel Query Builder;
- validated filters;
- controlled analytics schemas.

P16 explicitly avoids arbitrary natural-language SQL.

## XSS Considerations

React escapes normal string interpolation.

Blade reports use escaped output by default.

## CSRF / Session

Sanctum stateful authentication requires the frontend and backend environment to maintain the correct CSRF/session configuration.

## AI Security

P15 and P16 enforce:

- controlled context;
- prompt injection protection;
- evidence validation;
- no arbitrary SQL;
- no database credentials in prompts;
- structured output validation.

---

# 27. Responsible AI Design

CricIntel separates three categories of intelligence.

## Deterministic Analytics

Used when the answer can be calculated directly from cricket data.

Examples:

- strike rate;
- economy;
- phase statistics;
- matchup results.

## Predictive Machine Learning

Used for bounded forecasts.

Examples:

- batter score range;
- bowler economy;
- first-innings total.

## Generative AI

Used only to explain or synthesize authorized structured evidence.

This separation reduces hallucination and makes the architecture more trustworthy.

---

# 28. Database & Data Flow

A simplified operational relationship:

```text
Organization
 ├── Club
 │    └── Team
 │         ├── Season
 │         ├── Players
 │         ├── Training
 │         ├── Squads
 │         └── Fixtures
 │
 ├── Tournament
 │    └── Fixtures
 │
 ├── Matches
 │    └── Innings
 │         └── Deliveries
 │              └── Wickets
 │
 ├── Scouting
 ├── Strategy Plans
 ├── AI Strategy Runs
 ├── NL Analytics Queries
 ├── Notifications
 └── Report Exports
```

---

# 29. Realtime Architecture

## Match Realtime

Private channel:

```text
match.{matchId}
```

Used for P8 match updates.

## User Realtime

Private channel:

```text
App.Models.User.{id}
```

Used for P17 personal notifications.

---

# 30. Queue Architecture

CricIntel uses Laravel queues for asynchronous work.

## Notifications

Queue:

```text
notifications
```

Example worker:

```powershell
php artisan queue:work --queue=notifications --sleep=2 --tries=4 --timeout=30
```

## Reports

Queue:

```text
reports
```

Example worker:

```powershell
php artisan queue:work --queue=reports --sleep=2 --tries=3 --timeout=120
```

## General Worker

```powershell
php artisan queue:work --sleep=2 --tries=3 --timeout=120
```

---

# 31. Reporting Architecture

P18 is intentionally separated into reusable reporting services.

Examples:

```text
ReportAccessService
ReportDataService
ReportDocument
ReportFilenameService
ReportExporterFactory
ReportGenerator
GenerateReportExport
```

Exporter structure:

```text
ReportExporter
├── PdfReportExporter
├── ExcelReportExporter
└── CsvReportExporter
```

This makes additional report formats/types easier to maintain.

---

# 32. Main API Areas

The project API is organized under:

```text
/api/v1
```

Major API areas include:

```text
/auth
/organizations
/clubs
/teams
/seasons
/players
/venues
/tournaments
/fixtures
/matches
/statistics
/analytics
/squads
/training
/scouting
/opponent-analytics
/strategy
/predictive
/strategy-assistant
/nl-analytics
/notifications
/notification-preferences
/reports
```

## P18 Reporting Routes

```text
GET    /api/v1/organizations/{organization}/reports/options
POST   /api/v1/organizations/{organization}/reports/preview
POST   /api/v1/organizations/{organization}/reports/exports
GET    /api/v1/organizations/{organization}/reports/exports
GET    /api/v1/organizations/{organization}/reports/exports/{reportExport}
GET    /api/v1/organizations/{organization}/reports/exports/{reportExport}/download
DELETE /api/v1/organizations/{organization}/reports/exports/{reportExport}
```

## P17 Notification Routes

```text
GET    /api/v1/notifications
GET    /api/v1/notifications/unread-count
POST   /api/v1/notifications/{notification}/read
POST   /api/v1/notifications/{notification}/unread
POST   /api/v1/notifications/read-all
DELETE /api/v1/notifications/{notification}
GET    /api/v1/notification-preferences
PUT    /api/v1/notification-preferences
```

## P16 Natural-Language Analytics Routes

```text
GET  /api/v1/organizations/{organization}/nl-analytics/options
POST /api/v1/organizations/{organization}/nl-analytics/parse
POST /api/v1/organizations/{organization}/nl-analytics/query
GET  /api/v1/organizations/{organization}/nl-analytics/history
GET  /api/v1/organizations/{organization}/nl-analytics/history/{nlAnalyticsQuery}
```

## P15 Strategy Assistant Routes

```text
GET  /api/v1/organizations/{organization}/strategy-assistant/status
GET  /api/v1/organizations/{organization}/strategy-assistant/options
GET  /api/v1/organizations/{organization}/strategy-assistant/matches/{match}/context
POST /api/v1/organizations/{organization}/strategy-assistant/matches/{match}/ask
GET  /api/v1/organizations/{organization}/strategy-assistant/history
```

## P14 Predictive Routes

```text
GET  /api/v1/organizations/{organization}/predictive/options
GET  /api/v1/organizations/{organization}/predictive/readiness
POST /api/v1/organizations/{organization}/predictive/train
GET  /api/v1/organizations/{organization}/predictive/models/{modelKind}
POST /api/v1/organizations/{organization}/predictive/batters/{player}/score
POST /api/v1/organizations/{organization}/predictive/bowlers/{player}/economy
POST /api/v1/organizations/{organization}/predictive/teams/{team}/total
GET  /api/v1/organizations/{organization}/predictive/players/{player}/form
```

---

# 33. Frontend Routes

Major UI routes include:

```text
/login
/register
/dashboard
/organizations
/organizations/:organizationId
/organizations/:organizationId/players
/organizations/:organizationId/tournaments
/organizations/:organizationId/analytics
/organizations/:organizationId/opponent-intelligence
/organizations/:organizationId/strategy
/organizations/:organizationId/strategy/:planId
/organizations/:organizationId/predictive
/organizations/:organizationId/strategy-assistant
/organizations/:organizationId/nl-analytics
/organizations/:organizationId/reports
/notifications
/notification-preferences
```

The application includes:

- fixed/collapsible sidebar;
- mobile drawer behavior;
- responsive layout;
- breadcrumbs;
- role-aware navigation;
- organization context;
- topbar search/profile/notification controls.

---

# 34. Local Development Setup

## Requirements

### Backend

- PHP 8.3+
- Composer
- Laravel dependencies

### Frontend

- Node.js 20+
- npm

### Database

- PostgreSQL 18

### AI Service

- Python 3.11
- virtual environment

### Realtime

- Laravel Reverb

### Reporting Packages

```powershell
composer require barryvdh/laravel-dompdf phpoffice/phpspreadsheet
```

---

# 35. Running the System

## PostgreSQL

Current local development configuration can use:

```text
Host: 127.0.0.1
Port: 5433
Database: cricintel
User: cricintel_user
```

Use your actual local password in `backend/.env`.

## Backend

```powershell
cd F:\My_Projects\CricIntel\cricintel\backend

composer install

php artisan optimize:clear

php artisan migrate

php artisan serve
```

Backend:

```text
http://127.0.0.1:8000
```

## Frontend

```powershell
cd F:\My_Projects\CricIntel\cricintel\frontend

npm install

npm run dev
```

Frontend:

```text
http://localhost:5173
```

## Reverb

```powershell
cd F:\My_Projects\CricIntel\cricintel\backend

php artisan reverb:start
```

## Queue Worker

```powershell
cd F:\My_Projects\CricIntel\cricintel\backend

php artisan queue:work --sleep=2 --tries=3 --timeout=120
```

## AI Service

```powershell
cd F:\My_Projects\CricIntel\cricintel\ai-service

.\.venv\Scripts\Activate.ps1

pip install -r requirements.txt

uvicorn app.main:app --host 127.0.0.1 --port 8100 --reload
```

Health endpoint:

```text
http://127.0.0.1:8100/health
```

---

# 36. Testing

## Laravel

Run all backend tests:

```powershell
cd F:\My_Projects\CricIntel\cricintel\backend

php artisan test
```

Run milestone-specific tests:

```powershell
php artisan test --filter=P16
php artisan test --filter=P17
php artisan test --filter=P18
```

## Python AI Service

```powershell
cd F:\My_Projects\CricIntel\cricintel\ai-service

.\.venv\Scripts\Activate.ps1

pytest -q
```

## Frontend Production Build

```powershell
cd F:\My_Projects\CricIntel\cricintel\frontend

npm run build
```

---

# 37. Recommended Demo Data

For a meaningful portfolio demonstration, populate enough varied data.

Recommended:

```text
Organizations:         2
Clubs:                 2+
Teams:                 3
Seasons:               2
Players:               18–24
Venues:                3
Tournaments:           2
Fixtures:              8–12
Completed T20 matches: 6+
Training sessions:     6–10
Scouting profiles:     5+
Strategy plans:        3+
```

## Player Diversity

Include:

- right-handed batters;
- left-handed batters;
- pace bowlers;
- left-arm pace;
- off-spin;
- left-arm spin;
- all-rounders;
- wicketkeepers;
- injured/unavailable players.

## Match Diversity

Do not create identical matches.

Useful sample scenarios:

1. strong powerplay;
2. middle-over collapse;
3. death-over acceleration;
4. left-handed batter success;
5. strong death bowler performance;
6. high-scoring chase.

This produces more meaningful:

- P12 opponent intelligence;
- P14 predictions;
- P15 strategy evidence;
- P16 natural-language analytics;
- P18 reports.

---

# 38. End-to-End Demo Workflow

A strong demonstration order is:

```text
Login
  ↓
Organization
  ↓
Club
  ↓
Team
  ↓
Season
  ↓
Players
  ↓
Tournament / Fixtures
  ↓
Match Scoring
  ↓
Statistics
  ↓
Analytics
  ↓
Squad / Playing XI
  ↓
Training
  ↓
Scouting
  ↓
Opponent Intelligence
  ↓
Tactical Planning
  ↓
Predictive Analytics
  ↓
Grounded AI Strategy
  ↓
Natural-Language Analytics
  ↓
Notifications
  ↓
Professional Reports
```

For an interview:

1. show the dashboard;
2. explain organization hierarchy;
3. open players;
4. open a completed match;
5. explain delivery-level scoring;
6. show statistics/analytics;
7. show squad and Playing XI;
8. show training/development;
9. show scouting/recruitment;
10. show opponent intelligence;
11. show tactical planning;
12. show prediction;
13. show grounded AI evidence;
14. ask a P16 natural-language question;
15. show notifications;
16. generate a P18 report.

---

# 39. Project Strengths

CricIntel AI demonstrates more than simple CRUD.

## Full-Stack Engineering

The application includes:

- authentication;
- RBAC;
- complex relational data;
- APIs;
- validation;
- file handling;
- pagination;
- filters;
- responsive UI;
- realtime updates;
- queues;
- reporting;
- machine learning;
- responsible AI.

## Domain Modeling

Cricket concepts are represented as real application entities instead of generic demo records.

## Data Consistency

Delivery-level scoring acts as the foundation for analytics and reports.

## Operational Depth

The project includes:

- selection;
- training;
- scouting;
- tactical collaboration;
- notifications;
- reporting.

## Responsible AI

The platform deliberately avoids unsafe shortcuts such as:

- direct LLM database access;
- arbitrary AI-generated SQL;
- unsupported numeric tactical claims.

---

# 40. Real-World Industry Value

Potential users include:

- cricket academies;
- cricket clubs;
- school/university teams;
- domestic cricket organizations;
- analysts;
- selectors;
- coaches;
- performance departments;
- sports technology companies.

The platform can help organizations:

- centralize operational data;
- improve player tracking;
- improve selection evidence;
- analyze opponent behavior;
- maintain tactical knowledge;
- automate reporting;
- create auditable AI-assisted decisions.

---

# 41. Portfolio Description

## Short Version

**CricIntel AI — Intelligent Cricket Team Management, Match Analytics & Tactical Decision Platform**

Full-stack cricket operations platform built with Laravel, React, TypeScript, PostgreSQL, Reverb, FastAPI and scikit-learn. Supports multi-role organization/team/player management, delivery-level scoring, statistics, analytics, squads/Playing XI, training, scouting, opponent intelligence, tactical collaboration, predictive ML, grounded AI strategy assistance, controlled natural-language analytics, realtime notifications and professional PDF/Excel/CSV reporting.

## Portfolio Highlights

- Laravel 12 REST API with Sanctum authentication and role-based authorization.
- React/TypeScript frontend with professional responsive navigation and analytics UI.
- Deterministic match scoring and cricket statistics engine.
- Realtime match centre using Laravel Reverb and Echo.
- Availability-aware squad and Playing XI workflows.
- Training, player development, scouting and recruitment modules.
- Opponent intelligence using cricket phase, handedness and bowling-style analytics.
- Local FastAPI/scikit-learn prediction service.
- Grounded AI strategy assistant with evidence/provenance validation.
- Natural-language analytics using controlled intents rather than arbitrary SQL.
- Event-driven database/email/realtime notifications.
- Professional PDF, Excel and CSV reporting with queued export processing.

---

# 42. CV Description

## Full-Stack Software Engineering CV

**CricIntel AI | Laravel, React, TypeScript, PostgreSQL, FastAPI, Reverb**

Built an end-to-end cricket operations and decision-support platform covering organization/team/player CRUD, tournament and fixture management, delivery-level scoring, statistics, analytics, squad/Playing XI selection, training, scouting, tactical collaboration, realtime notifications and queued PDF/Excel/CSV reporting. Integrated a local ML service and responsible AI workflows with evidence validation and controlled natural-language analytics.

## AI / Security-Aware Engineering CV

**CricIntel AI | Secure Full-Stack & AI Engineering**

Designed organization-scoped RBAC, private realtime channels, validated domain workflows, grounded AI strategy responses with evidence IDs, and natural-language analytics that never allows arbitrary LLM-generated SQL. Integrated Laravel, React, PostgreSQL, Reverb, FastAPI and scikit-learn across a complete cricket decision-support platform.

---

# 43. Future Improvements

CricIntel is complete through P18.

Possible future improvements should focus on production maturity rather than immediately adding more product modules.

Examples:

- larger realistic dataset;
- deeper automated frontend testing;
- richer statistics query optimization;
- production Redis caching;
- deployment automation;
- infrastructure monitoring;
- audit logs;
- stronger model calibration;
- more predictive targets;
- richer report visualizations;
- multi-tenant SaaS deployment model;
- mobile companion application;
- external live cricket data integrations.

---

# 44. Current Status

```text
P1  Authentication & RBAC                         COMPLETE
P2  Organization / Club / Team / Season          COMPLETE
P3  Player Management                             COMPLETE
P4  Venues / Tournaments / Fixtures              COMPLETE
P5  Match Scoring Engine                          COMPLETE
P6  Statistics                                    COMPLETE
P7  Analytics Dashboard                           COMPLETE
P8  Realtime Match Centre                         COMPLETE
P9  Squad Selection & Playing XI                 COMPLETE
P10 Training & Player Development                COMPLETE
P11 Scouting & Recruitment                       COMPLETE
P12 Opponent Intelligence                        COMPLETE
P13 Tactical Planning & Collaboration            COMPLETE
P14 Predictive Analytics                         COMPLETE
P15 Grounded AI Strategy Assistant               COMPLETE
P16 Controlled Natural-Language Analytics        COMPLETE
P17 Notifications                                COMPLETE
P18 Professional Reporting                       COMPLETE
```

## Overall Project Status

**Implemented through P18.**

The current focus should be:

1. populate a realistic demo database;
2. run the complete P1–P18 workflow;
3. test positive and negative cases;
4. verify realtime/queue/AI/report workflows;
5. capture professional screenshots;
6. add CricIntel AI to portfolio, GitHub README, and CV.

---

# Author

**Gihan Shavinda Rathnayake**

Information Technology graduate with interests in:

- full-stack software engineering;
- cybersecurity;
- cloud engineering;
- AI/ML;
- secure AI systems;
- realtime applications;
- sports technology.

---

# Disclaimer

CricIntel AI is an engineering and decision-support platform. Predictive and AI-generated outputs should be treated as supporting evidence rather than guaranteed outcomes. Authoritative cricket decisions remain with coaches, selectors, analysts, and team staff.
