<?php

namespace Tackle\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

class AskUser extends AbstractTool
{
    public function description(): string
    {
        return 'Present the user with a choice and return their selection. Use when you have identified multiple valid options and want the user to decide which to pursue. Prefer this over guessing. Pass multiple=true to allow selecting more than one option.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'question' => $schema->string()
                ->description('The question to ask the user.')
                ->required(),
            'options' => $schema->array()
                ->description('The list of options to present.')
                ->required(),
            'multiple' => $schema->boolean()
                ->description('Allow the user to select multiple options. Defaults to false.'),
        ];
    }

    public function handle(Request $request): string
    {
        $question = $request->string('question', 'Choose an option:');
        $options  = (array) $request->get('options', []);
        $multiple = $request->boolean('multiple', false);

        if (empty($options)) {
            return 'No options were provided.';
        }

        $this->newline();

        if ($multiple) {
            $selected = multiselect(label: $question, options: $options, required: true);
            return implode(', ', $selected);
        }

        return select(label: $question, options: $options);
    }

    private function newline(): void
    {
        echo PHP_EOL;
    }
}
