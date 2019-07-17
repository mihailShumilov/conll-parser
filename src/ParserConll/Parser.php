<?php


namespace ParserConll;

/**
 * Class Parser
 * @package ParserConll
 */
class Parser {

    /**
     * @var string
     */
    private $conllText = '';

    /**
     * @var array
     */
    private $entities = [];

    /**
     * @var array
     */
    private $tree = [];

    /**
     * Parser constructor.
     *
     * @param string $conllText
     */
    public function __construct(string $conllText) {
        $this->conllText = $conllText;
    }

    /**
     *
     */
    public function parse(): void {
        $lines = explode("\n", $this->conllText);
        foreach ($lines as $line) {
            $this->processSingleLine($line);
        }

        $this->tree = $this->buildTreeFromObjects($this->entities);

    }

    /**
     * @param string $line
     */
    private function processSingleLine(string $line) {
        $this->entities[] = new Entity($line);
    }

    /**
     * @param $items
     *
     * @return array|mixed
     */
    private function buildTreeFromObjects($items) {
        $childs = [];

        foreach ($items as $item) {
            $childs[$item->getParentID() ?? 0][] = $item;
        }

        foreach ($items as $item) {
            if (isset($childs[$item->getId()])) {
                $item->childs = $childs[$item->getId()];
            }
        }

        return $childs[0] ?? [];
    }

    /**
     * @return array
     */
    public function getEntities(): array {
        return $this->entities;
    }

    /**
     * @return mixed
     */
    public function getTree() {
        return $this->tree;
    }

    /**
     * @return false|string
     */
    public function toJSON(){
        return json_encode($this->tree);
    }


}
