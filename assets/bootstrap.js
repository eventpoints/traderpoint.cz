import {startStimulusApp} from '@symfony/stimulus-bundle';

import CharacterCounter from 'stimulus-character-counter'
import PasswordVisibility from 'stimulus-password-visibility'
import TextareaAutogrow from 'stimulus-textarea-autogrow'
import Lightbox from 'stimulus-lightbox'
import ImageGrid from 'stimulus-image-grid'
import ReadMore from '@stimulus-components/read-more'
import AnimatedNumber from '@stimulus-components/animated-number'
import Clipboard from '@stimulus-components/clipboard'

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp();

// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
app.register('character-counter', CharacterCounter)
app.register('textarea-autogrow', TextareaAutogrow)
app.register("password-visibility", PasswordVisibility)
app.register('image-grid', ImageGrid)
app.register('lightbox', Lightbox)
app.register('read-more', ReadMore)
app.register('animated-number', AnimatedNumber)
app.register('clipboard', Clipboard)
