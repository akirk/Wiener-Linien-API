<?php

// adapted from http://us.php.net/manual/en/ref.xmlwriter.php#89047
class XmlConstruct extends XMLWriter {
	public function __construct($rootElement, $ns = false) {
		$this->openMemory();
		$this->setIndent(true);
		$this->setIndentString('    ');
		$this->startDocument('1.0', 'UTF-8');
		
		if ($ns) $this->startElementNS(null, $rootElement, $ns);
		else $this->startElement($rootElement);
	}

	public function fromArray($array, $parentTag = "item") {
		if (!is_array($array)) return;
		
		foreach ($array as $index => $element) {
			$tag = is_numeric($index) ? $parentTag : $index;
			if (is_array($element)) {
				$this->startElement($tag);
				$this->fromArray($element, $tag);
				$this->endElement();
			} elseif (substr($tag, 0, 1) == "_") {
				$this->writeAttribute(substr($tag, 1), $element);
			} else {
				$this->writeElement($tag, $element);
			}
		}
	}
	
	public function getDocument(){
		$this->endElement();
		$this->endDocument();
		return $this->outputMemory();
	}
}
