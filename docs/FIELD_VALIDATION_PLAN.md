# AgriShield field validation plan

No item below is marked complete. Tests must use consented real-world data, named reviewers and recorded evidence; synthetic/contract tests are engineering checks only.

| Track | Required evaluation | Evidence and acceptance gate |
| --- | --- | --- |
| Hausa and low-literacy experience | Native speakers review terminology, pronunciation, clarity, icon/voice pairing and error states across farmer flows. | Approved glossary, audio/text corrections, comprehension tasks and reviewer sign-off. |
| Agronomic advice | Qualified agronomists review sources, fact/inference/action distinction, threshold applicability, uncertainty and unsafe recommendations. | Versioned review decisions, rejected claims, escalation protocol and signed clinical-style safety checklist. |
| Pest/disease diagnosis | Collect consented labeled images from representative crops, devices and lighting; compare model results with independent ground truth. | Confusion matrix by crop/condition, false-negative review, model/version provenance and decision on safe use. |
| Satellite anomaly | Ground-check time-aligned field locations, cloud masks, crop stage and actual causes of vegetation change. | Georeferenced inspection records, false-alert rate, missed-event rate and minimum-history rule. |
| Farmer usability | Observe farmers completing Today, map, scouting, voice, photo, sync recovery and follow-up tasks without coaching. | Task success/time, comprehension, accessibility issues, incident log and fixes retested. |
| Offline device testing | Use actual low-end Android devices and weak/intermittent networks; switch accounts, restart mid-upload, and test media persistence and storage pressure. | Device/OS matrix, no cross-account disclosure, exactly-once server outcome, conflict handling and battery/storage measurements. |
| Yield data collection | Record weighed harvest, area, crop/variety, season, management and provenance; audit missingness and bias. | Verified labeled dataset and a separate model-development approval; no accuracy claim before holdout evaluation. |
| Pilot consent and privacy | Explain data collection, media, location, provider transfer, finance separation, withdrawal and retention in the participant's language. | Approved consent script, consent records, withdrawal drill and data-rights response rehearsal. |

## Exit decision

The product, agronomy, security/privacy and operations owners jointly approve a bounded pilot only after each applicable gate has evidence and an incident/rollback owner. Public production requires an additional deployment and legal review. An untested or blocked gate remains open; it is never inferred from a passing automated test.
