<?php

namespace Tests\Support;

use Mpdf\Mpdf;
use ZipArchive;

/** builds real PDF and Word CVs on the fly, so the tests read the same files a person would upload */
final class SampleResumes
{
    public const TEXT = <<<'CV'
SAM TAYLOR
sam.taylor@example.com | +44 7700 900123 | linkedin.com/in/samtaylor
Personal Statement
Second-year Computer Science student looking for a placement year in software engineering.
Education
BSc Computer Science, Aston University, 2024 to 2028
A-levels: Maths A, Physics B, Computing A, 2024
Experience
Software Intern, Acme Ltd, June 2025 to September 2025
Built a reporting dashboard in React used by 40 staff
Reduced page load time by 35% by caching API calls
Responsible for writing unit tests
Retail Assistant, Tesco, 2023 to present
Skills
Python, Java, JavaScript, React, SQL, Git, Docker, Communication, Teamwork
Projects
Built a CV website with Laravel and Vue
CV;

    public static function pdf(string $dir, string $text = self::TEXT): string
    {
        $mpdf = new Mpdf(['tempDir' => sys_get_temp_dir()]);
        $mpdf->WriteHTML('<p>'.implode('</p><p>', array_map('htmlspecialchars', explode("\n", $text))).'</p>');
        $path = $dir.'/cv.pdf';
        $mpdf->Output($path, 'F');

        return $path;
    }

    /** @param  bool  $layoutProblems  adds a table and puts the contact details in the page header */
    public static function docx(string $dir, string $text = self::TEXT, bool $layoutProblems = false): string
    {
        $paragraphs = implode('', array_map(fn ($line) => '<w:p><w:r><w:t>'.htmlspecialchars($line, ENT_XML1).'</w:t></w:r></w:p>', explode("\n", $text)));
        if ($layoutProblems) {
            $paragraphs .= '<w:tbl><w:tr><w:tc><w:p><w:r><w:t>Cell</w:t></w:r></w:p></w:tc></w:tr></w:tbl>';
        }
        $path = $dir.'/cv.docx';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$paragraphs.'</w:body></w:document>');
        if ($layoutProblems) {
            $zip->addFromString('word/header1.xml', '<?xml version="1.0"?><w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p><w:r><w:t>sam@example.com</w:t></w:r></w:p></w:hdr>');
        }
        $zip->close();

        return $path;
    }
}
