<?php


namespace ParserConll;

/**
 * Class Entity
 * @package ParserConll
 */
class Entity implements \JsonSerializable {

    public const ROLE_NAME  = 'name';
    public const ROLE_APPOS = 'appos';
    public const ROLE_DOBJ  = 'dobj';
    public const ROLE_AMOD  = 'amod';
    public const ROLE_NMOD  = 'nmod';
    public const ROLE_CASE  = 'case';
    public const ROLE_CONJ  = 'conj';
    public const ROLE_DET   = 'det';
    public const ROLE_CC    = 'cc';
    public const ROLE_NSUBJ = 'nsubj';


    public const WORD_FORM_VERB = 'VERB';
    public const WORD_FORM_ADV  = 'ADV';
    public const WORD_FORM_ADJ  = 'ADJ';
    public const WORD_FORM_NOUN = 'NOUN';
    public const WORD_FORM_DET  = 'DET';
    public const WORD_FORM_CONJ = 'CONJ';
    public const WORD_FORM_PART = 'PART';
    public const WORD_FORM_ADP  = 'ADP';


    public const ATTR_ANIMACY_ANIM = 'anim';
    public const ATTR_ANIMACY_INAN = 'inan';
    public const ATTR_CASE_DAT     = 'dat';
    public const ATTR_CASE_NOM     = 'nom';

    public const ATTRIBUTE_ANIMACY = 'Animacy';
    public const ATTRIBUTE_CASE    = 'Case';


    /**
     * @var string
     */
    private $line;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $word;

    /**
     * @var string
     */
    private $wordForm;

    /**
     * @var array
     */
    private $attributes;

    /**
     * @var string
     */
    private $parentID;

    /**
     * @var string
     */
    private $role;

    /**
     * Entity constructor.
     */
    public function __construct(string $line) {
        $this->line = $line;
        $this->parse();
    }

    /**
     *
     */
    protected function parse() {
        $parts = explode("\t", $this->line);

        $this->id         = $parts[0];
        $this->word       = $parts[1];
        $this->wordForm   = $parts[3];
        $this->attributes = $this->parseAttributes($parts[5]);
        $this->parentID   = $parts[6];
        $this->role       = $parts[7];
    }

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getWord() {
        return $this->word;
    }

    /**
     * @return mixed
     */
    public function getWordForm() {
        return $this->wordForm;
    }

    /**
     * @return mixed
     */
    public function getAttributes() {
        return $this->attributes;
    }

    /**
     * @return mixed
     */
    public function getParentID() {
        return $this->parentID;
    }

    /**
     * @return mixed
     */
    public function getRole() {
        return $this->role;
    }

    /**
     * @param string $data
     *
     * @return array
     */
    protected function parseAttributes(string $data): array {
        $parts = explode('|', $data);
        $attr  = [];
        foreach ($parts as $part) {
            list($key, $val) = explode('=', $part);
            $attr[$key] = $val;
        }

        return $attr;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize() {
        return get_object_vars($this);
    }


}
