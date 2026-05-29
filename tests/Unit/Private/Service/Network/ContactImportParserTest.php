<?php

declare(strict_types=1);

namespace App\Tests\Unit\Private\Service\Network;

use App\Enum\Network\ContactImportSource;
use App\Private\Service\Network\ContactImportParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ContactImportParserTest extends TestCase
{
    public function testItParsesLinkedInCsvRowsWithNormalizedFields(): void
    {
        $parser = new ContactImportParser();

        $rows = $parser->parseContent(<<<'CSV'
First Name,Last Name,URL,Email Address,Company,Position,Connected On
Tom,Test,https://www.linkedin.com/in/tom-test,tom.test@example.com,Test Lab,Developer,29 May 2026
CSV, ContactImportSource::LinkedInConnectionsCsv);

        self::assertCount(1, $rows);
        $this->assertRowContains($rows[0], [
            'display_name' => 'Tom Test',
            'first_name' => 'Tom',
            'last_name' => 'Test',
            'organization' => 'Test Lab',
            'role' => 'Developer',
            'main_channel' => 'LinkedIn',
            'email' => ['tom.test@example.com'],
            'profile_url' => 'https://www.linkedin.com/in/tom-test',
            'priority' => 'moyenne',
            'relationship_status' => 'a_relancer',
            'last_contact_at' => '29 May 2026',
        ]);
    }

    public function testItParsesLinkedInUploadedFile(): void
    {
        $parser = new ContactImportParser();
        $path = tempnam(sys_get_temp_dir(), 'linkedin-import-');
        self::assertNotFalse($path);

        try {
            file_put_contents($path, <<<CSV
First Name,Last Name,URL,Email Address,Company,Position,Connected On
Lina,Test,https://www.linkedin.com/in/lina-test,lina.test@example.com,Test Lab,Engineer,23 May 2026
CSV);

            $uploadedFile = new UploadedFile($path, 'Connections.csv', 'text/csv', null, true);
            $rows = $parser->parseUploadedFile($uploadedFile, ContactImportSource::LinkedInConnectionsCsv);

            self::assertCount(1, $rows);
            $this->assertRowContains($rows[0], [
                'display_name' => 'Lina Test',
                'email' => ['lina.test@example.com'],
                'profile_url' => 'https://www.linkedin.com/in/lina-test',
                'last_contact_at' => '23 May 2026',
            ]);
        } finally {
            @unlink($path);
        }
    }

    public function testItParsesVCardRowsAndFallsBackToNWhenFnIsMissing(): void
    {
        $parser = new ContactImportParser();

        $rows = $parser->parseContent(<<<'VCF'
BEGIN:VCARD
VERSION:2.1
N:Vander;Jean;;;
ORG:Phone Lab
TITLE:Developer
TEL;CELL;PREF:+32470123456
TEL;HOME:+32470123457
EMAIL;PREF:jean.vander@example.com
EMAIL;HOME:jean.vander.alt@example.com
URL:https://example.com/jean-vander
END:VCARD
VCF, ContactImportSource::PhoneVCard);

        self::assertCount(1, $rows);
        $this->assertRowContains($rows[0], [
            'display_name' => 'Jean Vander',
            'first_name' => 'Jean',
            'last_name' => 'Vander',
            'organization' => 'Phone Lab',
            'role' => 'Developer',
            'main_channel' => 'email',
            'email' => ['jean.vander@example.com', 'jean.vander.alt@example.com'],
            'phone' => ['+32470123456', '+32470123457'],
            'profile_url' => 'https://example.com/jean-vander',
        ]);
    }

    public function testItDecodesQuotedPrintableVCardValues(): void
    {
        $parser = new ContactImportParser();

        $rows = $parser->parseContent(<<<'VCF'
BEGIN:VCARD
VERSION:2.1
N;CHARSET=UTF-8;ENCODING=QUOTED-PRINTABLE:=44=75=70=75=79;=45=6D=69=6C=69=65;;;
FN;CHARSET=UTF-8;ENCODING=QUOTED-PRINTABLE:=45=6D=69=6C=69=65=20=44=75=70=75=79
ORG;CHARSET=UTF-8;ENCODING=QUOTED-PRINTABLE:=43=6F=64=65=78=20=51=41
TITLE:Developer
TEL:+33651898438
EMAIL:emilie.dupuy@example.com
END:VCARD
VCF, ContactImportSource::PhoneVCard);

        self::assertCount(1, $rows);
        $this->assertRowContains($rows[0], [
            'display_name' => 'Emilie Dupuy',
            'first_name' => 'Emilie',
            'last_name' => 'Dupuy',
            'organization' => 'Codex QA',
            'role' => 'Developer',
            'main_channel' => 'email',
            'email' => ['emilie.dupuy@example.com'],
            'phone' => ['+33651898438'],
        ]);
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $expected
     */
    private function assertRowContains(array $row, array $expected): void
    {
        foreach ($expected as $key => $value) {
            self::assertArrayHasKey($key, $row);
            self::assertSame($value, $row[$key]);
        }
    }
}
