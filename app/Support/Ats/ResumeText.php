<?php

namespace App\Support\Ats;

use App\Models\Cv;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;
use ZipArchive;

/**
 * The plain text an applicant tracking system would see in a CV file, plus the layout features
 * that commonly stop those systems reading it: tables, images, columns, text boxes and contact
 * details placed in the page header. Files are read in memory and never kept.
 */
final readonly class ResumeText
{
    /** @param  array<string, int|bool>  $signals */
    public function __construct(public string $text, public string $format, public array $signals = []) {}

    public static function fromFile(string $path, ?string $extension = null): self
    {
        $extension = strtolower($extension ?? pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => self::fromPdf($path),
            'docx' => self::fromDocx($path),
            default => throw new UnreadableResume('Only PDF and Word (.docx) files can be checked.'),
        };
    }

    public static function fromPdf(string $path): self
    {
        try {
            $pdf = (new PdfParser)->parseFile($path);
        } catch (Throwable) {
            throw new UnreadableResume('This PDF could not be read. It may be damaged or password protected.');
        }

        $text = self::tidy($pdf->getText());

        return new self($text, 'pdf', [
            'pages' => count($pdf->getPages()),
            'images' => count($pdf->getObjectsByType('XObject', 'Image')),
            // a PDF with almost no text is usually a scan or an image, which these systems cannot read
            'scanned' => mb_strlen($text) < 150,
        ]);
    }

    public static function fromDocx(string $path): self
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true || ($xml = $zip->getFromName('word/document.xml')) === false) {
            throw new UnreadableResume('This Word file could not be read. Save it as .docx and try again.');
        }

        $headers = '';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (preg_match('#^word/header\d*\.xml$#', $name)) {
                $headers .= self::docxText((string) $zip->getFromName($name)).' ';
            }
        }
        $zip->close();

        preg_match_all('/<w:cols\b[^>]*w:num="(\d+)"/', $xml, $columns);

        return new self(self::tidy(self::docxText($xml)), 'docx', [
            'tables' => substr_count($xml, '<w:tbl>') + substr_count($xml, '<w:tbl '),
            'images' => substr_count($xml, '<w:drawing') + substr_count($xml, '<w:pict'),
            'text_boxes' => substr_count($xml, '<w:txbxContent'),
            'columns' => max([1, ...array_map('intval', $columns[1])]),
            // details in the page header are skipped by many systems
            'header_contact' => (bool) preg_match('/@|\+?\d[\d\s]{8,}/', $headers),
        ]);
    }

    /**
     * A CV built on Vitafolio, laid out with the same standard headings its PDF uses. The owner's
     * email is included because the check is private to them and a real application would carry it.
     */
    public static function fromCv(Cv $cv): self
    {
        $cv->loadMissing(['user', 'tags', 'projects']);
        $user = $cv->user;
        $contact = array_filter([$user->email, $user->location, preg_replace('/[\s|,]+/', ' ', (string) $user->links)]);
        $parts = [$user->name, implode(' | ', $contact)];
        $sections = [
            'Profile' => $cv->profile,
            'Experience' => $cv->experience,
            'Education' => $cv->education,
            'Skills' => $cv->tags->pluck('name')->implode(', '),
            'Projects' => $cv->projects->map(fn ($p) => trim($p->title.' '.$p->description))->implode("\n"),
        ];
        foreach ($sections as $heading => $body) {
            if (filled($body)) {
                $parts[] = $heading;
                $parts[] = (string) $body;
            }
        }

        return new self(self::tidy(implode("\n", $parts)), 'vitafolio');
    }

    public static function fromString(string $text): self
    {
        return new self(self::tidy($text), 'vitafolio');
    }

    private static function docxText(string $xml): string
    {
        $xml = str_replace(['</w:p>', '<w:br/>', '<w:cr/>'], "\n", $xml);
        $xml = str_replace('<w:tab/>', "\t", $xml);

        return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private static function tidy(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\u{00A0}"], ["\n", "\n", ' '], $text);
        $lines = array_map(fn (string $line) => trim(preg_replace('/[ \t]+/', ' ', $line)), explode("\n", $text));

        return trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines)));
    }
}
