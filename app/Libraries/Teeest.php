<?php

class TextInput
{
    private $text;

    public function add(String $text)
    {
        $this->text .= $text;
    }

    public function getValue()
    {
        return $this->text;
    }
}

class NumericInput extends TextInput
{
    public function add($number)
    {
        if (is_numeric($number))
            parent::add($number);
    }
}

$input = new NumericInput();
$input->add('1');
$input->add('a');
$input->add('0');
echo $input->getValue();

$s = 'qqq';
$b = $a + 12;

# asdgasdfg
