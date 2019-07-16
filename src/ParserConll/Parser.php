<?php


namespace ParserConll;


class Parser {
    private $conllText = '';

    public function __construct(string $conllText) {
        $this->conllText = $conllText;

    }
}
