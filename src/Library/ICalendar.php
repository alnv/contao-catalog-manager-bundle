<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Symfony\Component\HttpKernel\EventListener\ValidateRequestListener;

class ICalendar {

    protected $arrEntity = [];

    public function __construct( $arrEntity ) {

        $this->arrEntity = $arrEntity;
    }

    public function getICalendarUrl() {

        global $objPage;

        return 'catalog-manager/icalendar/?t='. ( $this->arrEntity['_table'] ) .'&i='. ( $this->arrEntity['id'] ) .'&p=' . $objPage->id;
    }


    public function getICalFile() {

        global $objPage;

        $strStartTime = $this->arrEntity['roleResolver']()->getValueByRole('startTime');
        $strStartDate = $this->arrEntity['roleResolver']()->getValueByRole('startDate');
        $strEndTime = $this->arrEntity['roleResolver']()->getValueByRole('endTime');
        $strEndDate = $this->arrEntity['roleResolver']()->getValueByRole('endDate');
        $strLocation = $this->arrEntity['roleResolver']()->getValueByRole('location');
        $strTeaser = $this->arrEntity['roleResolver']()->getValueByRole('teaser');
        $strTitle = $this->arrEntity['roleResolver']()->getValueByRole('title');
        $strCity = $this->arrEntity['roleResolver']()->getValueByRole('city');
        $strStart = $this->setICalendarDate( $strStartDate . ( $strStartTime ? ' ' . $strStartTime : '' ), ( $strStartTime ? $objPage->dateFormat . ' ' . $objPage->timeFormat : $objPage->dateFormat ) );
        $strEnd = $this->setICalendarDate( $strEndDate . ( $strEndTime ? ' ' . $strEndTime : '' ), ( $strEndTime ? $objPage->dateFormat . ' ' . $objPage->timeFormat : $objPage->dateFormat ) );

        $arrICalFormat = [
            'BEGIN:' => 'VEVENT',
            'DTSTART:' => $strStart,
            'DTEND:' => $strEnd,
            'LOCATION:' => $strCity . ( $strLocation ? ', ' . $strLocation : '' ),
            'DTSTAMP:' => date( 'Ymd\THis', time() ),
            'SUMMARY:' => $strTitle,
            'URL;VALUE=URI:' => \Environment::get('uri'),
            'DESCRIPTION:' => $strTeaser,
            'UID:' => md5( $this->arrEntity['id'] ),
            'END:' => 'VEVENT'
        ];

        $strFile =
            "BEGIN:VCALENDAR" . "\r\n" .
            "VERSION:2.0" . "\r\n".
            "PRODID:https://catalog-manager.org" . "\r\n";

        foreach ( $arrICalFormat as $strFieldname => $strValue ) {

            if ( !$strValue ) {

                continue;
            }

            $strFile .= $strFieldname . $strValue . "\r\n";
        }

        $strFile .= 'END:VCALENDAR';

        return $strFile;
    }


    protected function setICalendarDate( $strDate, $strFormat ) {

        if ( !$strDate ) {

            return '';
        }

        return date( 'Ymd\THis', (new \Date( $strDate, $strFormat ))->tstamp );
    }
}