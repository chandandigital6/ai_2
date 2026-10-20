# Growya Release 3 — यहाँ से शुरू करें

1. **AI_PLANS_SETUP_HINDI.md** — इस AI update की migration, seeders, queue और admin/owner steps।
2. Super admin sidebar → **AI plans & approvals** (`/admin/ai-plans`)। Draft plans की अपनी कीमत/model/limits set करके active करें।
3. Owner → business → **AI studio** → package request → super admin approval → brand setup / saved prompts → generate।
4. **docs/AI_STUDIO_API.md** — current AI API contract; पुराने Release 2 AI sections इसके द्वारा superseded हैं।
5. **docs/API_FUNCTIONS_GUIDE_HINDI.md** — हर API का controller/function और बदलाव कहाँ करें।
6. **docs/api-v2/** — Flutter developer को यह पूरा folder दें; 411 API routes वाला updated Postman pack है।
7. **docs/RELEASE_3_VALIDATION.md** — executed checks और testing limits।

Existing non-AI modules की provider setup details PENDING_MODULES_SETUP_HINDI.md में हैं। Live deployment / paid provider generation इस package में नहीं किया गया। Server की existing .env और APP_KEY preserve करें।
