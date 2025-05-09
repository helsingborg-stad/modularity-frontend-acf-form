@if (!empty($step['fields']))
    @paper([
        'padding' => 4,
        'attributeList' => [
            'data-js-frontend-form-step-container' => $index,
        ]
    ])
        @element([
            'classList' => [
                'mod-frontend-form__step-header',
            ]
        ])
            @element([])
                @if($step['title'])
                    @typography([
                        'element' => 'h2',
                    ])
                        {{ $step['title'] }}
                    @endtypography
                @endif
                @if($step['title'])
                    @typography([
                        'classList' => ['u-margin__top--1']
                    ])
                        Beskrivning av steg
                    @endtypography
                @endif
            @endelement
            @button([
                'icon' => 'edit',
                'size' => 'md',
                'style' => 'basic',
                'classList' => [
                    'mod-frontend-form__step-header-edit',
                    $index === 0 ? 'u-visibility--hidden' : ''
                ],
                'attributeList' => [
                    'role' => 'button',
                    'data-js-frontend-form-step-edit' => $index,
                ]
            ])
            @endbutton
        @endelement
            @element([
                'attributeList' => [
                    'data-js-frontend-form-step' => $index,
                ],
                'classList' => [
                    'mod-frontend-form__step',
                    $index === 0 ? 'is-visible' : ''
                ]
            ])
                @foreach($step['fields'] as $field)
                    @if (isset($field['view']))  
                        @includeIf('fields.' . $field['view'], ['field' => $field])
                    @endif
                @endforeach
            @endelement
    @endpaper
@endif
