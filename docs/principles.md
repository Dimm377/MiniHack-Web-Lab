# Learning principles

MiniHack Web Lab is a discovery-first web security learning lab.

Learners move from web fundamentals through observation, experimentation,
discovery, and understanding to real-world security context. A flag marks a
discovery; reusable understanding is the goal.

## Core learning loop

Observe → Hypothesize → Experiment → Discover → Understand → Apply

## Teaching philosophy

Context first.
Discovery next.
Explanation after.

Learn how the web works before breaking it. Requests, responses, URLs, query
parameters, methods, headers, cookies, sessions, authentication, authorization,
server behavior, and browser-visible state are the foundation. Security
techniques should emerge from understanding those mechanisms.

Start with a situation, observable behavior, and a small objective. Give enough
context to begin and enough room to form a theory. Difficulty should come from
reasoning about behavior, not arbitrary guessing or copying a payload.

Observation is a skill to practice:

- What changed?
- What did the browser send?
- What did the server return?
- What am I not inspecting yet?
- What differs between two requests?

## Failure philosophy

Struggle enough to think,
not enough to stall.

Offer three optional, progressive hints: direction, concept, then action.
Keep hints collapsed until requested. The final hint may suggest a concrete
experiment but never supplies the flag. Provide a way to investigate without
hints; do not require arbitrary words that can only be found in a hint.

Hints carry no penalties, timers, analytics, or stored viewing state. Native
HTML and forms keep investigation and solving usable without JavaScript.

## Post-solve philosophy

Discover first.
Explain why.
Connect it to the real world.
Leave something worth remembering.

Only after a recorded solve, show **Why It Worked**, **Real-World Relevance**,
and **What To Remember**. Explain cause, behavior, and result in a short read,
then leave two to four transferable takeaways. Do not ship the full explanation
in unsolved HTML, even as hidden content.

HTTP primitives are not vulnerabilities. Distinguish the primitive from an
insecure implementation and its possible consequence. Connect discoveries to
development, debugging, HTTP inspection, and realistic application assessment.

## Implementation boundaries

Keep the secure application baseline separate from intentional challenge
mechanics. Learning content is static educational text; it never derives from
secrets, private data, or another account's progress. Reuse the existing
user/challenge solve state to gate explanations.

Real requests, responses, parameters, headers, methods, cookies, status codes,
and application behavior give MiniHack its technical identity. Preserve native
semantics, keyboard access, visible focus, and readable headings. Prefer simple,
inspectable PHP arrays and HTML; complexity must earn its place.
