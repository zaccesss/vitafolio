<?php

namespace Tests\Feature;

use App\Models\Cv;
use App\Models\User;
use App\Support\Locales;
use App\Support\PdfCv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use ZipArchive;

class DocumentLanguageTest extends TestCase
{
    use RefreshDatabase;

    /** a short real profile in each language, so the pdf has to draw that script */
    private const SAMPLES = [
        'en' => 'Builds accessible tools for small teams.',
        'es' => 'Desarrollo herramientas accesibles para equipos pequeños.',
        'fr' => 'Je conçois des outils accessibles pour de petites équipes.',
        'pt_BR' => 'Desenvolvo ferramentas acessíveis para equipes pequenas.',
        'zh_CN' => '为小型团队开发无障碍工具。',
        'ar' => 'أطوّر أدوات سهلة الاستخدام للفرق الصغيرة.',
        'ur' => 'میں چھوٹی ٹیموں کے لیے آسان رسائی والے ٹولز بناتا ہوں۔',
    ];

    public static function languages(): array
    {
        return collect(Locales::ALL)->map(fn ($language, $code) => [$code])->all();
    }

    public function test_a_new_cv_starts_in_the_owner_interface_language(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->actingAs($user)->post(route('cvs.store'), ['title' => 'Mon CV'])->assertRedirect();

        $this->assertSame('fr', $user->cvs()->where('title', 'Mon CV')->value('language'));
    }

    public function test_the_document_language_is_chosen_in_the_editor(): void
    {
        $cv = Cv::factory()->create();
        $settings = ['title' => $cv->title, 'slug' => $cv->slug, 'visibility' => 'public', 'theme' => 'classic', 'accent' => 'midnight', 'font' => 'sans'];

        $this->actingAs($cv->user)->get(route('cvs.edit', [$cv, 'settings']))->assertOk()
            ->assertSee('name="language"', false)
            ->assertSee('<option value="ar" lang="ar"', false)
            ->assertSee('العربية');

        $this->put(route('cvs.settings', $cv), [...$settings, 'language' => 'ar'])->assertSessionHasNoErrors();
        $this->assertSame('ar', $cv->fresh()->language);

        $this->put(route('cvs.settings', $cv), [...$settings, 'language' => 'klingon'])->assertSessionHasErrors('language');
        $this->assertSame('ar', $cv->fresh()->language);
    }

    public function test_the_cv_page_uses_its_own_language_whatever_the_visitor_reads(): void
    {
        $cv = Cv::factory()->create(['language' => 'ar', 'profile' => self::SAMPLES['ar'], 'cover_letter' => 'نص الخطاب']);

        // an english visitor gets an english page around an arabic, right-to-left cv
        $response = $this->get(route('cv.show', $cv), ['Accept-Language' => 'en-GB'])->assertOk();
        $response->assertSee('<html lang="en-GB" dir="ltr"', false);
        $response->assertSee('lang="ar" dir="rtl"', false);
        $response->assertSee(__('Profile', [], 'ar'));
        $response->assertSee(__('Share and download'));

        $this->get(route('cv.letter', $cv), ['Accept-Language' => 'fr'])->assertOk()
            ->assertSee('<html lang="fr" dir="ltr"', false)
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee(__('Cover letter', [], 'ar'));
    }

    public function test_the_word_file_carries_the_document_language(): void
    {
        $cv = Cv::factory()->create(['language' => 'zh_CN', 'profile' => self::SAMPLES['zh_CN']]);

        $docx = (string) $this->get(route('cv.word', $cv))->assertOk()->getContent();
        $path = tempnam(sys_get_temp_dir(), 'docx');
        file_put_contents($path, $docx);
        $zip = new ZipArchive;
        $zip->open($path);
        $this->assertStringContainsString('<dc:language>zh-CN</dc:language>', (string) $zip->getFromName('docProps/core.xml'));
        $this->assertStringContainsString('w:eastAsia="zh-CN"', (string) $zip->getFromName('word/styles.xml'));
        $this->assertStringContainsString(__('Profile', [], 'zh_CN'), (string) $zip->getFromName('word/document.xml'));
        $zip->close();
        @unlink($path);
    }

    public function test_pdf_data_follows_the_document_language(): void
    {
        $cv = Cv::factory()->create(['language' => 'ur', 'profile' => self::SAMPLES['ur']]);
        $data = PdfCv::data($cv, false, false);

        $this->assertSame('ur', $data['lang']);
        $this->assertSame('rtl', $data['dir']);
        $this->assertSame('Noto Nastaliq Urdu', $data['script_fonts'][0]);
        $this->assertSame(__('Profile', [], 'ur'), $data['sections'][0]['heading']);
        $this->assertSame('ltr', PdfCv::data(Cv::factory()->create(['language' => 'pt_BR']), false, false)['dir']);
    }

    #[DataProvider('languages')]
    public function test_the_pdf_is_tagged_with_the_document_language(string $code): void
    {
        if (PdfCv::binary() === null && ! getenv('CI')) {
            $this->markTestSkipped('typst is not installed, so tagged pdfs cannot be checked here.');
        }
        $cv = Cv::factory()->create([
            'language' => $code,
            'profile' => self::SAMPLES[$code]."\n\n- ".self::SAMPLES[$code],
            'experience' => self::SAMPLES[$code],
            'cover_letter' => self::SAMPLES[$code],
        ]);
        $cv->projects()->create(['title' => self::SAMPLES[$code], 'url' => 'https://example.com/work', 'description' => self::SAMPLES[$code]]);
        $cv->user->update(['links' => 'https://github.com/example']);

        foreach (['cv' => route('cv.pdf', $cv), 'letter' => route('cv.letter.pdf', $cv)] as $kind => $url) {
            $pdf = (string) $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf')->getContent();
            $this->assertStringContainsString('/Lang('.Locales::html($code).')', $pdf, "$kind pdf language for $code");
            $this->assertStringContainsString('pdfuaid:part>1<', $pdf);

            // set PDF_SAMPLES to a folder to keep each file for checking with verapdf
            if ($dir = getenv('PDF_SAMPLES')) {
                file_put_contents(rtrim($dir, '/')."/$code-$kind.pdf", $pdf);
            }
        }
    }
}
