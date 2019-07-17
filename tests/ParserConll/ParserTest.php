<?php declare(strict_types=1);

namespace ParserConll;


class ParserTest extends \PHPUnit\Framework\TestCase {

    /**
     * @dataProvider provider
     */
    public function testDataset(string $data): void {
        $this->assertTrue(mb_strlen($data) > 0, 'Dataset is empty');
    }

    /**
     * @dataProvider provider
     * @param string $data
     */
    public function testInit(string $data): void {
        $parser = new Parser($data);
        $this->assertTrue($parser instanceof Parser , 'Can\'t initialize parser');
    }

    /**
     * @dataProvider provider
     * @param string $data
     */
    public function testParse(string $data): void {
        $parser = new Parser($data);
        $parser->parse();
        $entityList = $parser->getEntities();
        $this->assertNotEmpty($entityList);
    }

    public function provider(): array {
        $text = file_get_contents(__DIR__ . '/../text/t4.conll');
        return [[$text]];
    }

}
