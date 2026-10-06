<?php

namespace App\Support;

use App\Models\Cv;
use RuntimeException;
use ZipArchive;

/**
 * builds a cv as a word document. A docx file is a zip of xml parts, so it is written directly
 * rather than through a library. It uses word's own title, heading and list styles, which screen
 * readers, applicant tracking systems and word's navigation pane all understand
 */
class WordCv
{
    public static function build(Cv $cv): string
    {
        $user = $cv->user;
        $body = self::header($cv);

        foreach ($cv->orderedSections() as $section) {
            if ($section === 'profile' && filled($cv->profile)) {
                array_push($body, self::paragraph('Profile', 'Heading1'), ...self::text($cv->profile));
            } elseif ($section === 'experience' && filled($cv->experience)) {
                array_push($body, self::paragraph('Experience', 'Heading1'), ...self::text($cv->experience));
            } elseif ($section === 'education' && filled($cv->education)) {
                array_push($body, self::paragraph('Education', 'Heading1'), ...self::text($cv->education));
            } elseif ($section === 'projects' && $cv->projects->isNotEmpty()) {
                array_push($body, self::paragraph('Projects', 'Heading1'), ...self::projects($cv));
            } elseif ($section === 'skills' && $cv->tags->isNotEmpty()) {
                array_push($body, self::paragraph('Skills', 'Heading1'), self::paragraph($cv->tags->pluck('name')->implode('  ·  ')));
            } elseif ($section === 'links' && ($links = Links::parse($user->links)) !== []) {
                $body[] = self::paragraph('Links', 'Heading1');
                foreach ($links as $link) {
                    $body[] = self::paragraph($link['label'].': '.$link['url'], 'ListBullet');
                }
            }
        }

        return self::package($cv, $user->name.' CV', $body);
    }

    /** the cv's cover letter under the same name and details, so the pair reads as one set */
    public static function letter(Cv $cv): string
    {
        $body = [...self::header($cv), self::paragraph('Cover letter', 'Heading1')];
        if (filled($cv->letter_to)) {
            $body[] = self::paragraph((string) $cv->letter_to, 'Subtitle');
        }
        array_push($body, ...self::text($cv->cover_letter));

        return self::package($cv, $cv->user->name.' cover letter', $body);
    }

    /** the name, headline and contact line that open both the cv and its letter */
    private static function header(Cv $cv): array
    {
        $user = $cv->user;
        $body = [self::paragraph($user->name, 'Title')];
        if ($cv->displayHeadline()) {
            $body[] = self::paragraph((string) $cv->displayHeadline(), 'Subtitle');
        }
        $details = array_filter([
            $user->pronouns, $user->location, $user->university,
            $cv->key_language ? 'Main language: '.$cv->key_language : null,
            $cv->show_email ? $user->email : null,
        ]);
        if ($details !== []) {
            $body[] = self::paragraph(implode('  ·  ', $details));
        }

        return $body;
    }

    private static function package(Cv $cv, string $title, array $body): string
    {
        $accent = ltrim(config('vitafolio.accents')[$cv->accent]['hex'] ?? '#14213d', '#');
        $path = tempnam(sys_get_temp_dir(), 'cvdocx');
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the Word file.');
        }
        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::rootRels());
        $zip->addFromString('docProps/core.xml', self::core($title));
        $zip->addFromString('word/_rels/document.xml.rels', self::documentRels());
        $zip->addFromString('word/styles.xml', self::styles($accent));
        $zip->addFromString('word/numbering.xml', self::numbering());
        $zip->addFromString('word/document.xml', self::document(implode('', $body)));
        $zip->close();
        $bytes = (string) file_get_contents($path);
        @unlink($path);

        return $bytes;
    }

    /** plain cv text: lines starting "- " become bullets, blank lines separate paragraphs */
    private static function text(?string $text): array
    {
        $out = [];
        foreach (preg_split('/\R/', trim((string) $text)) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $out[] = str_starts_with($line, '- ') ? self::paragraph(substr($line, 2), 'ListBullet') : self::paragraph($line);
        }

        return $out;
    }

    private static function projects(Cv $cv): array
    {
        $out = [];
        foreach ($cv->projects as $project) {
            $out[] = self::paragraph($project->title, 'Heading2');
            if ($project->url) {
                $out[] = self::paragraph($project->url);
            }
            array_push($out, ...self::text($project->description));
        }

        return $out;
    }

    private static function paragraph(string $text, ?string $style = null): string
    {
        $props = $style ? '<w:pPr><w:pStyle w:val="'.$style.'"/>'.($style === 'ListBullet' ? '<w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr>' : '').'</w:pPr>' : '';

        return '<w:p>'.$props.'<w:r><w:t xml:space="preserve">'.self::xml($text).'</w:t></w:r></w:p>';
    }

    /** text is escaped and characters xml cannot hold are dropped, so no input can break the file */
    private static function xml(string $text): string
    {
        $clean = (string) preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $text);

        return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function document(string $body): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$body
            .'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1000" w:right="1080" w:bottom="1000" w:left="1080" w:header="0" w:footer="0" w:gutter="0"/></w:sectPr>'
            .'</w:body></w:document>';
    }

    private static function styles(string $accent): string
    {
        $font = '<w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:docDefaults><w:rPrDefault><w:rPr>'.$font.'<w:sz w:val="22"/><w:lang w:val="en-GB"/></w:rPr></w:rPrDefault>'
            .'<w:pPrDefault><w:pPr><w:spacing w:after="80" w:line="264" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>'
            .'<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style>'
            .'<w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:basedOn w:val="Normal"/><w:pPr><w:spacing w:after="40"/></w:pPr><w:rPr><w:b/><w:color w:val="'.$accent.'"/><w:sz w:val="48"/></w:rPr></w:style>'
            .'<w:style w:type="paragraph" w:styleId="Subtitle"><w:name w:val="Subtitle"/><w:basedOn w:val="Normal"/><w:rPr><w:sz w:val="26"/></w:rPr></w:style>'
            .'<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:pPr><w:keepNext/><w:spacing w:before="280" w:after="80"/><w:pBdr><w:bottom w:val="single" w:sz="6" w:space="2" w:color="'.$accent.'"/></w:pBdr><w:outlineLvl w:val="0"/></w:pPr><w:rPr><w:b/><w:color w:val="'.$accent.'"/><w:sz w:val="28"/></w:rPr></w:style>'
            .'<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:pPr><w:keepNext/><w:spacing w:before="140" w:after="40"/><w:outlineLvl w:val="1"/></w:pPr><w:rPr><w:b/><w:sz w:val="23"/></w:rPr></w:style>'
            .'<w:style w:type="paragraph" w:styleId="ListBullet"><w:name w:val="List Bullet"/><w:basedOn w:val="Normal"/><w:pPr><w:spacing w:after="40"/></w:pPr></w:style>'
            .'</w:styles>';
    }

    private static function numbering(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:numbering xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:abstractNum w:abstractNumId="0"><w:lvl w:ilvl="0"><w:start w:val="1"/><w:numFmt w:val="bullet"/><w:lvlText w:val="•"/><w:lvlJc w:val="left"/><w:pPr><w:ind w:left="360" w:hanging="360"/></w:pPr></w:lvl></w:abstractNum>'
            .'<w:num w:numId="1"><w:abstractNumId w:val="0"/></w:num></w:numbering>';
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            .'<Override PartName="/word/numbering.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'</Types>';
    }

    private static function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'</Relationships>';
    }

    private static function documentRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/numbering" Target="numbering.xml"/>'
            .'</Relationships>';
    }

    private static function core(string $title): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:title>'.self::xml($title).'</dc:title><dc:language>en-GB</dc:language></cp:coreProperties>';
    }
}
