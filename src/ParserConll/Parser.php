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
     * @var array
     */
    private $usedTreeItems = [];

    public const ROLE_ROOT = 'ROOT';

    /**
     * @var Entity[]
     */
    private $persons = [];

    /**
     * @var integer[]
     */
    private $usedPersonsNodes = [];

    /**
     * @var string[]
     */
    private $subjectPositions = [];

    /**
     * @var string
     */
    private $message = '';

    /**
     * @var string
     */
    private $rootAction = '';

    /**
     * @var array
     */
    private $what = [];

    /**
     * @var array
     */
    private $whatEntity = [];

    /**
     * @var array
     */
    private $who = [];

    /**
     * @var array
     */
    private $whoEntity = [];

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
            if ($line !== '') {
                $this->processSingleLine($line);
            }
        }

        $this->buildTreeFromObjects();
        $this->processTree($this->tree);
        $this->message = $this->buildMessage($this->entities);
    }

    /**
     * @return string
     */
    public function getMessage(): string {
        return $this->message;
    }

    /**
     * @param $entities
     *
     * @return string
     */
    protected function buildMessage($entities): string {
        $wordArr = [];
        foreach ($entities as $entity) {
            $wordArr[$entity->getId()] = $entity->getWord();
        }
        ksort($wordArr);
        return implode(' ', $wordArr);
    }

    /**
     * @param string $line
     */
    private function processSingleLine(string $line) {
        $this->entities[] = new Entity($line);
    }

    /**
     *
     */
    private function buildTreeFromObjects(): void {
        $rootItem       = $this->getRootEntity();
        $item           = [];
        $item['entity'] = $rootItem;
        $item['child']  = $this->getChildItem($rootItem->getId());

        $this->tree[] = $item;
    }

    private function getRootEntity(): Entity {
        foreach ($this->entities as $entity) {
            if ($entity->getRole() === self::ROLE_ROOT) {
                return $entity;
            }
        }
    }

    private function getChildItem(int $parentId): array {
        $result = [];
        foreach ($this->entities as $entity) {
            if ($entity->getParentID() === $parentId) {
                $item           = [];
                $item['entity'] = $entity;
                $item['child']  = $this->getChildItem($entity->getId());
                $result[]       = $item;
            }
        }

        return $result;
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
    public function toJSON() {
        return json_encode($this->tree);
    }


    private function processTree(array $tree, int $level = 0): void {

        foreach ($tree as $levelData) {
            /**
             * @var Entity $entity
             */
            $entity = $levelData['entity'];

            //Check if that is ROOT
            if ($entity->getRole() === Entity::ROLE_ROOT) {
                $this->rootAction = $entity->getWord();

                //Detect WHAT
                if (!empty($levelData['child'])) {
                    $this->detectWhat($levelData['child'], $level + 1);
                }

                //Detect WHO
                if (!empty($levelData['child'])) {
                    $this->detectWho($levelData['child'], $level + 1);
                    $this->analyzeWho();
                }
            }


//            if (!empty($levelData['child'])) {
//                $this->processTree($levelData['child'], $level + 1);
//            }
        }
    }

    private function detectWhat(array $tree, int $level = 0): void {
        foreach ($tree as $levelData) {
            /**
             * @var Entity $entity
             */
            $entity = $levelData['entity'];
            if (in_array($entity->getRole(), [Entity::ROLE_DOBJ])) {
                $this->what[$entity->getId()]       = $entity->getWord();
                $this->whatEntity[$entity->getId()] = $entity;
                if (!empty($levelData['child'])) {
                    $this->detectWhatDetails($levelData['child'], $level + 1, true);
                }
                return;
            }
        }

        if (!empty($levelData['child'])) {
            $this->detectWhat($levelData['child'], $level + 1);
        }
        return;
    }


    private function detectWhatDetails(array $tree, int $level = 0, bool $firstCall = false): void {
        foreach ($tree as $levelData) {
            /**
             * @var Entity $entity
             */
            $entity = $levelData['entity'];
            if (in_array($entity->getRole(), [Entity::ROLE_NAME, Entity::ROLE_AMOD, Entity::ROLE_NMOD],
                         false) && $firstCall) {
                $this->what[$entity->getId()]       = $entity->getWord();
                $this->whatEntity[$entity->getId()] = $entity;
                if (!empty($levelData['child'])) {
                    $this->detectWhatDetails($levelData['child'], $level + 1, true);
                }
            }
        }

        if (!empty($levelData['child'])) {
            $this->detectWhoDetails($levelData['child'], $level + 1);
        }
    }

    private function detectWho(array $tree, int $level = 0): void {
        foreach ($tree as $levelData) {
            /**
             * @var Entity $entity
             */
            $entity = $levelData['entity'];
            if (in_array($entity->getRole(), [Entity::ROLE_NSUBJ], false)) {
                $this->who[$entity->getId()]       = $entity->getWord();
                $this->whoEntity[$entity->getId()] = $levelData;
                if (!empty($levelData['child'])) {
                    $this->detectWhoDetails($levelData['child'], $level + 1, true);
                }
                return;
            }
        }

        if (!empty($levelData['child'])) {
            $this->detectWho($levelData['child'], $level + 1);
        }
    }

    private function detectWhoDetails(array $tree, int $level = 0, bool $firstCall = false): void {
        foreach ($tree as $levelData) {
            /**
             * @var Entity $entity
             */
            $entity = $levelData['entity'];
            if (in_array($entity->getRole(), [Entity::ROLE_CC, Entity::ROLE_AMOD, Entity::ROLE_CONJ],
                         false) && $firstCall) {
                $this->who[$entity->getId()]       = $entity->getWord();
                $this->whoEntity[$entity->getId()] = $levelData;
                if (!empty($levelData['child'])) {
                    $this->detectWhoDetails($levelData['child'], $level + 1, true);
                }
            }
        }

        if (!empty($levelData['child'])) {
            $this->detectWhoDetails($levelData['child'], $level + 1);
        }
    }

    private function analyzeWho(): void {
        ksort($this->who, SORT_NUMERIC);

        $personRole = [];
        $lastId     = min(array_keys($this->who));

        foreach ($this->who as $index => $word) {
            if (($index - $lastId) > 1) {
                $name = $this->tryGetName($lastId);
                if (strlen($name)) {
                    $this->persons[] = [
                        'name' => $name,
                        'role' => implode(' ', $personRole)
                    ];
                }

                $personRole = [];
            }
            $personRole[$index] = $word;
            $lastId             = $index;
        }

        $name = $this->tryGetName($lastId);

        if (strlen($name)) {
            $this->persons[] = [
                'name' => $name,
                'role' => implode(' ', $personRole)
            ];
        }
    }

    private function tryGetName(int $entityId): string {
        $personNameArr = $this->getRoleName($this->whoEntity[$entityId]);
        $personName    = [];
        ksort($personNameArr);
        foreach ($personNameArr as $pnItem) {
            $personName[] = $pnItem->getWord();
        }
        return implode(' ', $personName);
    }

    private function getRoleName(array $entity): array {
        $name = [];
        /**
         * @var Entity $item
         */
        foreach ($entity['child'] as $item) {
            if (in_array($item['entity']->getRole(), [
                Entity::ROLE_AMOD,
                Entity::ROLE_NMOD,
                Entity::ROLE_NAME,
                Entity::ROLE_APPOS,
                Entity::ROLE_DOBJ,
                Entity::ROLE_PARATAXIS
            ], false)) {
                if (!isset($this->whoEntity[$item['entity']->getId()])) {
                    $name[$item['entity']->getId()] = $item['entity'];
                    if (!empty($item['child'])) {
                        $name += $this->getRoleName($item);
                    }
                }
            }
        }

        return $name;
    }


    /**
     * @return Entity[]
     */
    public function getPersons(): array {
        return $this->persons;
    }

    /**
     * @return array
     */
    public function getEntities(): array {
        return $this->entities;
    }

    private function getAllChild(array $child = []): array {
        $result = [];

        foreach ($child as $childData) {
            $item                   = $childData['entity'];
            $result[$item->getId()] = $item->getWord();
            if (!empty($childData['child'])) {
                $nextLevel = $this->getAllChild($childData['child']);
                $result    += $nextLevel;
            }
        }

        ksort($result, SORT_NUMERIC);

        return $result;
    }

}
