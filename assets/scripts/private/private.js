import '../../styles/private/private.css';
import './copy-to-clipboard.js';
import './contacts-list.js';
import { setupPrivateFormSubmitFeedback } from './form-submit-feedback.js';
import '../../scripts/theme-switcher.js';

setupPrivateFormSubmitFeedback(globalThis.document);
