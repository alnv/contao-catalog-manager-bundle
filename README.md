## Catalog-Manager v2

Zurzeit fehlt noch die Dokumentation, allerdings ist die Funktionsweise sehr stark an CM v1 angelehnt, sodass CM v1 Kenner problemlos mit CM v2 umgehen können. Anbei die wichtigsten Änderungen:

- **Wichtig: Diese Erweiterung nicht mit Catalog-Manager v1 installieren!**
- {{CTLG_ACTIVE}} Inserttag wird zu {{ACTIVE }}
- {{CTLG_MASTER}} Inserttag wird zu {{MASTER}} und keine Einstellungen mehr in der Seitenstruktur erforderlich.
- {{CTLG_TIMESTAMP}} wird zu {{TIMESTAMP}} z.B. {{TIMESTAMP::tstamp::+ 1 days}}
- Es gibt kein Filterformular mehr, stattdessen kann der Formulargenerator verwendet werden.
- Frontend-Editing momentan nicht verfügbar.
- Umkreissuche hat neue Feldnamen:
    - rs_pstl wird zu postal
    - rs_cty wird zu city
    - rs_strt wird zu street
    - rs_cntry wird zu country
    - rs_dstnc wird zu radius
