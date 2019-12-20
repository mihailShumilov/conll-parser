<?php declare(strict_types=1);

namespace ParserConll;


use Symfony\Component\VarDumper\VarDumper;

class ParserTest extends \PHPUnit\Framework\TestCase {

    /**
     * @dataProvider provider
     */
    public function testDataset(string $data): void {
        $this->assertTrue(mb_strlen($data) > 0, 'Dataset is empty');
    }

    /**
     * @dataProvider provider
     *
     * @param string $data
     */
    public function testInit(string $data): void {
        $parser = new Parser($data);
        $this->assertTrue($parser instanceof Parser, 'Can\'t initialize parser');
    }

    /**
     * @dataProvider provider
     *
     * @param string $data
     */
    public function testParse(string $data): void {
        $parser = new Parser($data);
        $parser->parse();
        $entityList = $parser->getEntities();
        $this->assertNotEmpty($entityList);

//        VarDumper::dump($parser->getTree());

        $persons = $parser->getPersons();
        $this->assertIsArray($persons);

        $this->assertIsString($parser->getMessage());

        echo $parser->getMessage().PHP_EOL;

        if(!empty($persons)) {
            echo PHP_EOL;
            print_r($persons);
            echo PHP_EOL;
        }
    }

    /**
     * @dataProvider provider
     *
     * @param string $data
     */
    public function testJson(string $data): void {
        $parser = new Parser($data);
        $parser->parse();
        $json = $parser->toJSON();

        $this->assertJson($json);
    }

    public function provider(): array {
        $data = [];
        $files = glob(__DIR__ . '/../text/*.conll');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data[]  = [$content];
        }
        return $data;
    }

}
