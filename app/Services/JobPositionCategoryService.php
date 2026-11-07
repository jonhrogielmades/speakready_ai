<?php

namespace App\Services;

use Illuminate\Support\Str;

class JobPositionCategoryService
{
    public const OTHER_CATEGORY = 'Other Interview Roles';

    /**
     * The configured catalog is authoritative. These narrower patterns only
     * classify titles that are not already in the catalog.
     */
    private const TITLE_PATTERNS = [
        'Maritime and Seafaring' => '/\b(?:seaman|seafarer|sailor|deckhand|deck hand|deck cadet|deck officer|deck crew|bosun|boatswain|ship captain|ship crew|ship engineer|marine engineer|marine electrician|marine mechanic|marine safety officer|engine cadet|engine officer|maritime|vessel crew|able bodied seaman|ordinary seaman|chief mate|first mate|second mate|third mate)\b/',
        'Retail and Personal Services' => '/\b(?:florist|floral|flower shop|retail|cashier|store associate|hairdresser|hairstylist|barber|beautician|cosmetologist|salon|tailor)\b/',
        'Healthcare' => '/\b(?:nurse|nursing|physician|doctor|medical|midwife|caregiver|healthcare|health care|pharmacist|pharmacy|therapist|dental|dentist|radiologic|paramedic)\b/',
        'Education' => '/\b(?:teacher|teaching|tutor|instructor|educator|school registrar|guidance counselor)\b/',
        'Agriculture and Fisheries' => '/\b(?:agricultur\w*|farm\w*|fisher\w*|aquacultur\w*|crop production|veterinary|marine biologist)\b/',
        'IT and Digital' => '/\b(?:software|developer|programmer|devops|web developer|data analyst|data scientist|data engineer|network administrator|network engineer|systems administrator|cybersecurity|cloud engineer|qa tester|qa analyst|information technology|it support|it specialist|it technician|ai engineer)\b/',
        'Tourism and Hospitality' => '/\b(?:tourism|tour guide|hotel|hospitality|housekeeping|restaurant|chef|cook|kitchen|barista|travel coordinator|food and beverage)\b/',
        'Finance and Business' => '/\b(?:accountant|accounting|bookkeep\w*|payroll|banking|financial|finance|tax associate|auditor|loan officer|budget analyst|business analyst)\b/',
        'Customer and Sales' => '/\b(?:customer service|customer support|customer success|call center|bpo|sales|marketing|account executive|business development|e commerce)\b/',
        'Logistics and Transportation' => '/\b(?:driver|delivery rider|warehouse|inventory|logistics|supply chain|dispatch|shipping clerk|forklift|fleet manager)\b/',
        'Skilled Trades and Maintenance' => '/\b(?:electrician|plumber|carpenter|welder|mechanic|hvac|air conditioning|maintenance technician|cleaner|janitor\w*|mason|painter|appliance repair|heavy equipment operator)\b/',
        'Creative and Media' => '/\b(?:content writer|copywriter|video editor|photographer|illustrator|multimedia|animator|videographer|graphic designer|ui ux designer|social media coordinator)\b/',
        'Public Safety and Community Services' => '/\b(?:security guard|social worker|firefighter|police officer|disaster response|emergency response|community development|community outreach|emergency medical technician)\b/',
        'Local Government / Office' => '/\b(?:administrative|office clerk|office manager|receptionist|records officer|human resources|executive assistant|barangay|municipal|procurement|public information officer|lgu)\b/',
        'Engineering' => '/\b(?:engineer|engineering|construction|drafting|geodetic|architect)\b/',
    ];

    public function categoryFor(string $position): ?string
    {
        $normalized = $this->normalize($position);
        if ($normalized === '') {
            return null;
        }

        $configured = (array) config('speakready_scope.job_positions', []);
        foreach ($configured as $category => $positions) {
            foreach ((array) $positions as $catalogPosition) {
                if ($this->normalize((string) $catalogPosition) === $normalized) {
                    return (string) $category;
                }
            }
        }

        foreach (self::TITLE_PATTERNS as $category => $pattern) {
            if (array_key_exists($category, $configured) && preg_match($pattern, $normalized)) {
                return $category;
            }
        }

        return null;
    }

    public function isAvailableCategory(string $category): bool
    {
        return $category === self::OTHER_CATEGORY
            || array_key_exists($category, (array) config('speakready_scope.job_positions', []));
    }

    private function normalize(string $position): string
    {
        $position = Str::ascii(Str::lower($position));

        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9]+/', ' ', $position) ?? '') ?? '');
    }
}
