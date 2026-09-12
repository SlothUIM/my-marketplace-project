# common-marketplace

An account-free, privacy-first local classifieds concept. Built because Facebook Marketplace is a data-harvesting nightmare riddled with bots, and Craigslist is great but looks like it was abandoned in 1999.

This is a prototype for a marketplace utility that does exactly one job: letting local people buy and sell stuff without tracking pixel slop, passwords, or corporate profile setups.

## How it works (The architecture)

Instead of a database full of user profiles, password hashes, and tracked interests, this platform uses a temporary transaction model.

1. **The 30-Second List:** A seller drops a few photos, sets a price, and puts down an email or phone number. 
2. **Permanent Magic Links:** When you publish, the site texts or emails you a unique cryptographic link (e.g., `/manage/8f3b9a12...`). This link is your absolute passkey. You bookmark it, and you use it to edit the price, mark the item as pending, or delete it when sold. If you lose the link, you lose access to the post. Simple as that.
3. **Anonymized Relays:** Buyers click a message button on the listing page. The system generates a temporary masked email relay so neither party ever sees the other’s real contact info.
4. **Masked Payments:** Sellers can link their Venmo or Cash App handle in a hidden background layer. The public listing page just shows a "Pay with Venmo" button. When tapped on mobile, it deep-links straight into the buyer's native banking app with the amount pre-filled, so bots can't scrape raw phone numbers or usernames off the open web.
5. **No Shipping / Local Only:** Everything is geofenced by ZIP codes. Transactions happen face-to-face in cash or instant P2P at cash swap spots (like a local police station parking lot). 

## Repo Structure

Built using plain HTML and CSS. No massive Node modules, no React bloat, no framework dependencies. Initial page weight is under 15KB.

* `index.html` - Minimalist homepage with category filters to find what you need quickly.
* `browse.html` - The chronological search grid with Active/Pending status badges.
* `post.html` - The fast visual drop form for sellers.
* `style.css` - Single stylesheet controlling colors, layouts, and system tokens.

## Running it locally

Just clone the folder and open `index.html` directly in any web browser. 

```bash
git clone https://github.com
cd common-marketplace
python -m http.server 8000
```

## 🤝 Contributing
The frontend interface is 100% complete and verified fluid. We are actively looking for backend developers and open-source architects to help write server endpoints:

- [ ] Stateless PHP/Node.js handlers for token management.
- [ ] Ephemeral email relay routing nodes.
- [ ] **Internet Categorization Ingest API Integration:** Replace our temporary local client dictionary script with an external asynchronous gateway fetch (e.g., uClassify REST API or Wikidata Semantic Lookups) to parse item context dynamically on submission.
- [ ] **Smart Geographic Radius Filtering:** Implement a backend routing check (e.g., via Open Source Routing Machine - OSRM) instead of basic Haversine straight-line distance math to prevent items across large water bodies or lakes from polluting a buyer's local 30-mile feed.

Feel free to fork the code and submit a pull request.
