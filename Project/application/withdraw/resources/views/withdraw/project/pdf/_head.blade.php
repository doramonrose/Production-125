<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin-footer: 2mm;
            footer: html_esignature;
        }
        @if (!empty($hideFirstPageEsignFooter))
        @page :first {
            footer: html_esignature;
        }
        @page memo {
            margin-top: 16mm;
            margin-header: 4mm;
            margin-footer: 2mm;
            header: html_memoPageNum;
            footer: html_esignature;
        }
        @page memo2 {
            margin-top: 16mm;
            margin-header: 4mm;
            margin-footer: 2mm;
            header: html_memoPageNum;
            footer: html_esignature;
        }
        @page memo2 :first {
            margin-top: 12mm;
            margin-footer: 2mm;
            header: none;
            footer: html_esignature;
        }
        @page doc {
            margin-top: 12mm;
            margin-footer: 2mm;
            footer: html_esignature;
        }
        @page bk06 {
            margin-top: 12mm;
            margin-footer: 2mm;
            footer: html_esignature;
        }
        @endif
        body, table, td, th, div, p, span, strong, b {
            font-family: thsarabunnew;
        }
        body {
            color: #000;
            font-size: 16pt;
            line-height: 1.15;
            margin: 0;
        }
        strong, b { font-weight: bold; }
        .topbar { width: 100%; border-collapse: collapse; margin-bottom: 0.5mm; }
        .topbar td { padding: 0; }
        .topbar-left, .topbar-right { width: 28mm; }
        .krut-img { height: 15mm; }
        .title { font-size: 22pt; font-weight: bold; text-align: center; line-height: 1.1; }
        .meta { width: 100%; border-collapse: collapse; margin-top: 1mm; margin-bottom: 1mm; }
        .meta td { padding: 0.3mm 0; vertical-align: bottom; line-height: 1.15; }
        .p {
            margin: 0 0 1.8mm;
            text-align: justify;
            line-height: 1.15;
            text-indent: 25mm;
        }
        .p-flush {
            margin: 0 0 1.8mm;
            text-align: left;
            line-height: 1.15;
            text-indent: 0;
        }
        .p-item {
            margin: 0 0 0.8mm;
            text-align: justify;
            line-height: 1.15;
            text-indent: 25mm;
        }
        .p-decision {
            margin: 2mm 0 0;
            text-align: left;
            line-height: 1.2;
            text-indent: 0;
            font-size: 16pt;
            font-family: thsarabunnew;
        }
        .approval-block {
            page-break-inside: avoid;
            font-size: 16pt;
            font-family: thsarabunnew;
        }
        .sign-approver-left { width: 55%; text-align: center; font-size: 16pt; font-family: thsarabunnew; }
        .sign-approver-left .sign-caption td { font-size: 16pt; font-family: thsarabunnew; line-height: 1.15; text-align: center; }
        .sign-approver-left .sign-text { font-size: 16pt; font-family: thsarabunnew; line-height: 1.15; text-align: center; }
        .sign-approver-right { width: 48%; text-align: center; font-size: 16pt; font-family: thsarabunnew; }
        .sign-approver-right .sign-caption td { font-size: 16pt; font-family: thsarabunnew; line-height: 1.15; text-align: center; }
        .sign-approver-right .sign-text { font-size: 16pt; font-family: thsarabunnew; line-height: 1.15; text-align: center; }
        .items-table, .simple-table { width: 100%; border-collapse: collapse; margin: 2mm 0; }
        .items-table th, .items-table td, .simple-table th, .simple-table td {
            border: 0.6pt solid #000;
            padding: 1.1mm 1.5mm;
            vertical-align: top;
            line-height: 1.15;
            font-size: 16pt;
        }
        .items-table th { text-align: center; font-weight: bold; font-size: 16pt; }
        .center { text-align: center; }
        .right { text-align: right; }
        .sign { width: 100%; border-collapse: collapse; margin-top: 4mm; }
        .sign td { vertical-align: top; padding: 0; }
        .sign td.sign-box { font-size: 16pt; font-family: thsarabunnew; }
        .sign-caption { width: 100%; border-collapse: collapse; margin: 0; font-size: 16pt; font-family: thsarabunnew; }
        .sign-caption td { padding: 0; line-height: 1.15; vertical-align: top; font-size: 16pt; font-family: thsarabunnew; }
        .sign-text { font-size: 16pt; font-family: thsarabunnew; line-height: 1.15; }
        .sign-box { width: 48%; line-height: 1.15; font-size: 16pt; font-family: thsarabunnew; }
        .sign-box-left { width: 48%; text-align: left; }
        .sign-hang-cell { padding-top: 0.5mm; }
        .sign-approver { width: 100%; border-collapse: collapse; margin-top: 4mm; }
        .sign-approver td { padding: 0; vertical-align: top; }
        .sign-approver-block { width: 95mm; text-align: center; line-height: 1.15; }
        .sign-img-cell { min-height: 14mm; padding-bottom: 1mm; vertical-align: bottom; }
        .sign-img { max-height: 14mm; max-width: 48mm; height: auto; width: auto; }
        .sign-img-hang {
            max-height: 7mm;
            max-width: 20mm;
            height: auto;
            width: auto;
        }
        .check { font-family: thsarabunnew; }
        .decision-list {
            width: 100%;
            border-collapse: collapse;
            margin: 2mm 0 0;
        }
        .decision-list > tr > td,
        .decision-list td.decision-check-wrap,
        .decision-list td.decision-option {
            padding: 0.3mm 0;
            vertical-align: middle;
            line-height: 1.2;
            border: 0;
        }
        .decision-check-wrap {
            width: 6mm;
        }
        .decision-check-cell {
            width: 3.4mm;
            height: 3.4mm;
            border: 0.7pt solid #111;
            text-align: center;
            vertical-align: middle;
            font-family: dejavusans, thsarabunnew;
            font-size: 8.5pt;
            font-weight: bold;
            line-height: 3.4mm;
            color: #0f766e;
            padding: 0;
        }
        .decision-check-cell.empty {
            color: #111;
            font-weight: normal;
        }
        .decision-option {
            font-size: 16pt;
            font-family: thsarabunnew;
            line-height: 1.2;
            padding-left: 1.5mm !important;
            vertical-align: middle;
        }
        .page-no { text-align: center; margin-bottom: 3mm; }
        .announce-title { font-size: 18pt; font-weight: bold; text-align: center; margin: 1.5mm 0; line-height: 1.15; }
        .announce-subject { font-size: 16pt; font-weight: bold; margin-bottom: 1.5mm; line-height: 1.15; }
        .announce-dots { letter-spacing: 0; margin: 0 0 4mm; line-height: 1.15; }
        .announce-winner {
            margin: 0 0 1.8mm;
            text-align: left;
            line-height: 1.15;
            text-indent: 0;
            padding-left: 25mm;
        }
        .announce-winner-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 1.8mm;
        }
        .announce-winner-table td {
            padding: 0;
            vertical-align: top;
            line-height: 1.15;
            font-size: 16pt;
        }
        .announce-winner-indent {
            width: 25mm;
        }
        .announce-winner-name,
        .announce-winner-amount {
            white-space: nowrap;
        }
        .announce-date {
            margin: 6mm 0 0;
            width: 100%;
            border-collapse: collapse;
        }
        .announce-date td { padding: 0; vertical-align: top; line-height: 1.15; }
        .announce-date-text { text-align: left; }
        .announce-krut { text-align: center; margin-top: 4mm; }
        .dotline { border-bottom: 0.4pt dotted #000; }
        .box { border: 0.7pt solid #000; padding: 3mm; }
        .ol-item { margin: 0.6mm 0; text-align: justify; line-height: 1.15; }
        .section-title { font-size: 16pt; font-weight: normal; text-align: left; margin: 0 0 1.8mm; line-height: 1.15; }
        .esign-footer {
            font-family: thsarabunnew;
            font-size: 12pt;
            line-height: 1.15;
            text-align: center;
            color: #000;
        }
    </style>
</head>
<body>

@if (!empty($hideFirstPageEsignFooter))
<htmlpageheader name="memoPageNum">
    <div class="esign-footer" style="font-size:16pt;">-{PAGENO}-</div>
</htmlpageheader>
@endif
<htmlpagefooter name="esignature">
    <div style="font-family: thsarabunnew, sans-serif; font-size: 12pt; line-height: 1.2; text-align: center; color: #000; margin: 0; padding: 0;">
        เอกสารฉบับนี้ใช้ลายมือชื่ออิเล็กทรอนิกส์ ตามพระราชบัญญัติ ว่าด้วยธุรกรรมทางอิเล็กทรอนิกส์ พ.ศ.2544
        <br>และข้อบังคับมหาวิทยาลัยเชียงใหม่ ว่าด้วยการใช้ลายมือชื่ออิเล็กทรอนิกส์ พ.ศ.2564
    </div>
</htmlpagefooter>
