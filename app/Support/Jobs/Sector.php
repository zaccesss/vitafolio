<?php

namespace App\Support\Jobs;

/**
 * The field a role belongs to, so people can browse their own area. A board's own category is used
 * when it has one; otherwise the title and company are matched against whole-word terms, most
 * specific field first.
 */
class Sector
{
    /** the fields in the order the filter lists them */
    public const ALL = [
        'software' => 'Software and IT',
        'hardware' => 'Hardware, electronics and embedded',
        'data' => 'Data and AI',
        'engineering' => 'Engineering',
        'finance' => 'Finance and banking',
        'business' => 'Business, consulting and management',
        'law' => 'Law',
        'health' => 'Healthcare and medicine',
        'science' => 'Science and research',
        'creative' => 'Marketing, media and creative',
        'education' => 'Education',
        'public' => 'Public sector and charity',
        'retail' => 'Retail, hospitality and customer service',
        'other' => 'Other',
    ];

    /** @return array<string, string> the fields in the reader's language, written out so the translation scanner sees them */
    public static function labels(): array
    {
        return [
            'software' => __('Software and IT'),
            'hardware' => __('Hardware, electronics and embedded'),
            'data' => __('Data and AI'),
            'engineering' => __('Engineering'),
            'finance' => __('Finance and banking'),
            'business' => __('Business, consulting and management'),
            'law' => __('Law'),
            'health' => __('Healthcare and medicine'),
            'science' => __('Science and research'),
            'creative' => __('Marketing, media and creative'),
            'education' => __('Education'),
            'public' => __('Public sector and charity'),
            'retail' => __('Retail, hospitality and customer service'),
            'other' => __('Other'),
        ];
    }

    /** adzuna category tags; general ones such as graduate-jobs are left to the title */
    private const ADZUNA = [
        'it-jobs' => 'software',
        'engineering-jobs' => 'engineering',
        'healthcare-nursing-jobs' => 'health',
        'social-work-jobs' => 'health',
        'legal-jobs' => 'law',
        'accounting-finance-jobs' => 'finance',
        'consultancy-jobs' => 'business',
        'hr-jobs' => 'business',
        'admin-jobs' => 'business',
        'sales-jobs' => 'business',
        'property-jobs' => 'business',
        'logistics-warehouse-jobs' => 'business',
        'teaching-jobs' => 'education',
        'scientific-qa-jobs' => 'science',
        'pr-advertising-marketing-jobs' => 'creative',
        'creative-design-jobs' => 'creative',
        'charity-voluntary-jobs' => 'public',
        'retail-jobs' => 'retail',
        'hospitality-catering-jobs' => 'retail',
        'customer-services-jobs' => 'retail',
        'travel-jobs' => 'retail',
        'manufacturing-jobs' => 'engineering',
        'energy-oil-gas-jobs' => 'engineering',
        'trade-construction-jobs' => 'engineering',
        'maintenance-jobs' => 'engineering',
    ];

    /** phrases that settle the field before the single terms below could mislead */
    private const PHRASES = [
        'quant developer' => 'finance', 'quantitative developer' => 'finance', 'quantitative researcher' => 'finance',
        'quant researcher' => 'finance', 'data center' => 'hardware', 'data centre' => 'hardware',
        'solutions architect' => 'software', 'software architect' => 'software', 'cloud architect' => 'software',
        'windows engineer' => 'software', 'linux engineer' => 'software', 'environmental engineer' => 'engineering',
        'investment banking' => 'finance', 'legal engineer' => 'law', 'project management' => 'business',
        'project manager' => 'business', 'programme management' => 'business', 'digital marketing' => 'creative',
    ];

    /** title terms per field, checked in this order so "embedded software" lands in hardware */
    private const TERMS = [
        'hardware' => ['embedded', 'firmware', 'fpga', 'asic', 'vlsi', 'electronic', 'electronics', 'hardware', 'pcb', 'semiconductor',
            'silicon', 'rf', 'microelectronics', 'microwave', 'dram', 'nand', 'cpu', 'gpu', 'chip', 'memory', 'wafer', 'fab', 'robotics', 'mechatronics', 'photonics', 'signal processing'],
        'data' => ['data', 'machine learning', 'ml', 'ai', 'artificial intelligence', 'analytics', 'data science', 'data scientist',
            'deep learning', 'nlp', 'computer vision', 'business intelligence', 'bi'],
        'software' => ['software', 'developer', 'devops', 'cloud', 'cyber', 'security engineer', 'it', 'technology', 'tech', 'web',
            'frontend', 'front-end', 'backend', 'back-end', 'full stack', 'full-stack', 'sre', 'platform', 'infrastructure',
            'network', 'networks', 'systems engineer', 'qa', 'test engineer', 'programmer', 'computing', 'digital'],
        'law' => ['law', 'legal', 'solicitor', 'barrister', 'paralegal', 'trainee solicitor', 'training contract', 'vacation scheme',
            'pupillage', 'compliance', 'regulatory'],
        'health' => ['nurse', 'nursing', 'healthcare', 'health', 'medical', 'clinical', 'pharmacy', 'pharmacist', 'physiotherapy',
            'physiotherapist', 'midwife', 'midwifery', 'dental', 'care assistant', 'support worker', 'nhs', 'radiography',
            'occupational therapy', 'psychology', 'mental health', 'social work', 'paramedic'],
        'finance' => ['finance', 'financial', 'banking', 'bank', 'investment', 'accounting', 'accountant', 'audit', 'tax', 'actuarial',
            'actuary', 'trading', 'trader', 'quant', 'quantitative', 'asset management', 'wealth', 'insurance', 'risk', 'treasury',
            'equity', 'markets', 'credit'],
        'science' => ['research', 'researcher', 'scientist', 'laboratory', 'lab', 'chemistry', 'chemist', 'biology', 'biologist',
            'physics', 'physicist', 'biotech', 'pharmaceutical', 'environmental', 'ecology', 'geoscience'],
        'public' => ['civil service', 'government', 'public sector', 'policy', 'council', 'charity', 'nonprofit', 'non-profit',
            'fast stream', 'police', 'local authority', 'housing'],
        'engineering' => ['engineer', 'engineering', 'mechanical', 'electrical', 'civil', 'structural', 'aerospace', 'manufacturing', 'process',
            'chemical engineering', 'automotive', 'nuclear', 'energy', 'construction', 'quantity surveyor', 'surveyor', 'design engineer'],
        'creative' => ['marketing', 'media', 'design', 'designer', 'creative', 'content', 'communications', 'pr', 'public relations',
            'journalism', 'journalist', 'advertising', 'brand', 'social media', 'copywriter', 'ux', 'ui', 'graphic', 'film',
            'production', 'publishing', 'editorial'],
        'education' => ['teacher', 'teaching', 'tutor', 'education', 'school', 'lecturer', 'teaching assistant', 'early years'],
        'business' => ['consultant', 'consulting', 'consultancy', 'business', 'management', 'operations', 'strategy', 'project',
            'product', 'sales', 'hr', 'human resources', 'recruitment', 'procurement', 'supply chain', 'logistics', 'commercial',
            'analyst', 'administrator', 'administration', 'office', 'property', 'real estate'],
        'retail' => ['retail', 'store', 'shop', 'hospitality', 'restaurant', 'hotel', 'bar', 'barista', 'catering', 'customer service',
            'customer assistant', 'sales assistant', 'cashier', 'waiter', 'waitress', 'events', 'travel', 'tourism', 'leisure'],
    ];

    /** the title decides first; adzuna files many roles under IT, so its category only fills a gap */
    public static function fromAdzuna(?string $tag, string $title, ?string $company = null): string
    {
        $fromTitle = self::guess($title);

        return $fromTitle !== 'other' ? $fromTitle : (self::ADZUNA[$tag ?? ''] ?? self::guess('', $company));
    }

    public static function guess(string $title, ?string $company = null): string
    {
        foreach ([$title, (string) $company] as $text) {
            $t = mb_strtolower($text);
            foreach (self::PHRASES as $phrase => $sector) {
                if (str_contains($t, $phrase)) {
                    return $sector;
                }
            }
            foreach (self::TERMS as $sector => $terms) {
                foreach ($terms as $term) {
                    if (preg_match('/(?<![\w])'.preg_quote($term, '/').'(?![\w])/u', $t)) {
                        return $sector;
                    }
                }
            }
        }

        return 'other';
    }
}
