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

    /**
     * @dataProvider provider
     * @param string $data
     */
    public function testJson(string $data): void {
        $parser = new Parser($data);
        $parser->parse();
        $json = $parser->toJSON();

        $this->assertJson($json);
    }

    public function provider(): array {
        $text1 = file_get_contents(__DIR__ . '/../text/t4.conll');
        $text2 = file_get_contents(__DIR__ . '/../text/t2.conll');
        $text3 = file_get_contents(__DIR__ . '/../text/t1.conll');
        return [[$text1],[$text2],[$text3]];
    }

}
