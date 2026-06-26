import '../../styles/private/private.css';
import './copy-to-clipboard.js';
import { setupContactPreparationModal } from './contact-preparation.js';
import './contacts-list.js';
import { setupPrivateFormSubmitFeedback } from './form-submit-feedback.js';
import { setupPrivateWebauthn } from './webauthn.js';
import '../../scripts/theme-switcher.js';

setupContactPreparationModal(globalThis.document);
setupPrivateFormSubmitFeedback(globalThis.document);
setupPrivateWebauthn(globalThis.document);
