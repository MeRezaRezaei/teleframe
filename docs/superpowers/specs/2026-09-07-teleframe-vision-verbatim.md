# Teleframe Vision — Owner's Verbatim Statement

> **Record type:** Raw verbatim, 2026-09-07. Spelling and typos corrected ONLY —
> wording, phrasing, coinages, and every idea preserved as spoken. Section
> headings added for navigation; they are not part of the original words.
> This document is the upstream source of truth for all future framework-layer
> specs and plans. When a plan conflicts with this, the plan is wrong.

---

## The core analogy: Laravel for Telegram

The Teleframe vision is having a framework for Telegram. I am just copying
concepts from the Laravel framework for web applications and adjusting them
into our Teleframe. Since I know those abilities of teleclient later on can be
built way better using my current explanations, I decided to share it with you.

The fact is Laravel's assumption is: there are some web requests that come to
our web service, and we make requests, validators, middlewares for them to
handle them properly. If you capture the same mindset and align it to Telegram
and our Teleframe, you can see that here the Laravel requests become Telegram
updates. Therefore our assumption is: Telegram makes some updates, we should
handle them. So we should react the same way that Laravel reacted to web
requests, with Telegram updates.

## The semi-route system ("uprate" = update as request)

Instead of having a web path, we have to make our routes based on what update
we want our app to react to. This shapes our semi-route system for Telegram
updates. Just like how routes make things clear on what path should call what
controller, our updates section should be made the same: the user indicates
what updates they want to receive, and therefore the update comes to some
controllers. We are just going to use the name **uprate** instead of request —
they are actually the same thing in the case of our app input for response.
And since it is already made, the possibilities for our update section become
way narrower than Laravel — I don't know what, but by applying the logic I am
asking to the matter and comparing with Telegram updates, you definitely can
be able to make that layer flawless.

## The two-layer separation and loop prevention

There is a separation too: any update that comes to us is a truth that
Telegram admitted it, and we can ignore it. The way the update handler keeps
the database updated about latest changes stays intact. We are just making a
NEW layer after it whose work is to let the user indicate what data they want
to work on and respond on. And of course in the previous layer there are some
updates that must not reach this layer since it makes a loop. But we can
prevent the loop if we carefully design our route system: whatever is
registered to respond can be eliminated as it comes again in the same section.
So what should we do more than only register things to capture updates to app
logic — those are the kind of updates that make the loops.

Using this view, until now we achieved two major features that any other app
in this area is missing:

1. **Background update handler** that keeps the database updated with the
   latest data, and
2. **Loop prevention** using the information and structure we make around to
   let the framework do the two friction sections that apps currently in the
   market do manually.

This update is huge and therefore needs a lot of correct plans, and those
plans need careful gap mining and friction mining, and planning for a way that
the gaps and frictions get handled. Only using this way can we have basics of
frameworks to use them.

## Inheriting Laravel's layers (validators, middleware, Eloquent)

Other layers of work like validators, middleware, and the things Laravel has
will be inherited — maybe with some justifications. For example: any Telegram
update is about a user, so the user id and finding a user using its id in the
users table of Laravel by Eloquent is a brand new feature, like
`User::findTF(telegram_user_id)`. Laravel already made it — we are going to
use its patterns, to only adjust ourselves to those patterns that Laravel
uses. What I explained is a PATTERN: I am not telling you to just make this
feature — I am telling you that the main work is about analyzing and comparing
Laravel to our framework and finding the exact set of adjustments and changes
we should do to make our app a REAL framework. More importantly: inheriting
Laravel capabilities and adding framework layers that let developers make new
apps using BOTH web and Telegram abilities — that is the exact vision I have.

## Services: one logic, two platforms

If you pay attention to patterns in my teleclient, you notice I am making
things like mini app responses as a Vue.js app. This is the exact thing that
later on can represent the app frontend hosted by Laravel, to be able to use
the same thing it is showing to web users, but in the form of a Telegram mini
app. This is possible only by following the pattern I am telling you to
analyze and build around.

Therefore all these can also have one separate approach: since service logic
is separated from controllers in Laravel, a developer can make services and
respond the same way to both web requests and Telegram updates — same logic,
two platforms. This is the thing that finally makes everything more powerful
than before. Suppose Laravel using the same database as our Teleframe, and
the query to find a user's Telegram account is just a query of finding the
same person in Eloquent — which therefore adds a trait to its User model:

- `HasTelegram` — to say we can contact this user using our Telegram account
- `HasUserTelegram` — to say the user has logged in to our Teleframe Telegram
  user app

These are therefore possible with a correct design — doing them passionately
but reachable.

## Three auth approaches

These days you can make bots and give the bot to act as your user account in
many aspects, which makes it more reliable and faster. That leaves three
approaches one can choose:

1. Log in to the user app (real user session),
2. Passing a bot capable of user abilities,
3. Passing a normal bot.

## The bot map (schema-map pattern applied to bots)

This also unlocks the ability to use OTHER bots in two ways: one,
bot-on-bot communication; two, user API to bot — that later on can make a
**bot map** that exposes the use of that bot as a function call, just the same
way we made the MTProto schema map. We can make a bot map and call it, or
even expose it as an API call, since normal web apps can't call Telegram
bots.

## Keyboard objects as routing sources

There is a fantastic thing we can make: builders of keyboards, inline
keyboards, commands — things that Telegram has and web does not have — and
wire them in a way that it exactly knows, based on the keyboard it made, that
the update coming to the update section belongs to the keyboard object of X
or Y, and sends the thing to the update layer. The update layer makes logic
for: "if it receives this key from THIS keyboard, it should call that
specific controller function or answer." This solves one of the biggest
frictions in Telegram development: you can separate your keyboards from the
code that must act on that call. This also can prevent injections to our
system.

## Blade-for-Telegram: message templates (the V in MVC)

I noticed something else too: the Blade in Laravel — if applied to this way —
I can conclude that we can make MESSAGE TEMPLATES based on the schema of
Telegram we have in our core app sections, and keep it in structure. The
behind-the-scenes work of matching matrix and word places can be cached inside
the bootstrap cache just like Blades, and later on gets into use as the
representation layer. This makes the MVC for Telegram, just how it must be
designed.

## The stage machine: same controller, telegram-paced intake

The only friction that exists is web applications are not state machines. If
we want to make the logic ONCE and use it all along the app, both for web and
Telegram, we should make some new structures to let the developer make the
web logic, then separate it into STAGES for Telegram. The submit is when the
predefined stages are filled and the data is the exact match that a form
inside a web app sends — with the little fact that one captures the data step
by step from the user and one receives all at once. It is not something that
does not have a way to handle: it is just about our stage machine, that also
relates to the routing update engine, that can send things step by step, and
when the stage is done, it calls THE SAME ROUTE for Laravel web to handle the
data — with a flag that says the return data should be sent to the Telegram
engine, not the web engine.

Only by adding a layer in Laravel that separates data templates from views,
and only accepting controllers to send those predefined templates, can we
manage any inconsistency between the Telegram way and the web way,
flawlessly.

## Methodology for building it

This is the full vision I have for this Teleframe. This update is huge, and
therefore the first next thing is taking steps based on this fact: store my
words inside docs as pure raw verbatims first — only correct my writing
spellings — and then we are going to use skills to let us make layer-by-layer
plans adjusted for this app. In fact I made the design that lets us solve any
misconception or friction, to let us have both web and Telegram app at the
same time, using framing our work based on the facts they carry.

---

## Navigation index (added by agent, not part of verbatim)

| # | Layer / concept | Laravel analogue | Status in codebase |
|---|---|---|---|
| 1 | Update intake → truth mirror | (none — ours alone) | ✅ built (Ingest) |
| 2 | Uprate routing → controllers | routes + controllers | ❌ to design (Phase 3 expansion) |
| 3 | Loop prevention via registration | (none) | ❌ to design |
| 4 | Validators / middleware for uprates | FormRequest / middleware | ❌ to design |
| 5 | `findTF`, `HasTelegram`, `HasUserTelegram` | Eloquent extensions | ❌ to design |
| 6 | Services dual-platform (web + TG) | services (exists) | ❌ wire-up |
| 7 | Mini app hosting (Vue via Laravel) | Blade/Vite | 🌱 pattern exists in teleclient |
| 8 | Auth trio (user / user-bot / bot) | guards | 🌱 engine supports, no guard layer |
| 9 | Bot map (bots as function calls/API) | (schema-map pattern) | ❌ to design |
| 10 | Keyboard objects as routing sources | (none) | ❌ to design |
| 11 | Message templates + bootstrap cache | Blade compilation | ❌ to design |
| 12 | Stage machine → same-route submit | forms | ❌ to design |

**Planning rule (owner's):** every layer gets gap-mining + friction-mining
before its plan; plans are written per layer (writing-plans format); the
Phase 0–4 roadmap in `2026-09-07-teleframe-unification-design.md` is the
substrate these layers build on.
