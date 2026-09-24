"use client";

import { useState } from "react";

export function ShareProductButton({ productName }: { productName: string }) {
  const [feedback, setFeedback] = useState("Share");

  async function share() {
    const data = { title: productName, text: productName, url: window.location.href };
    try {
      if (navigator.share) {
        await navigator.share(data);
        setFeedback("Shared");
      } else {
        await navigator.clipboard.writeText(window.location.href);
        setFeedback("Link copied");
      }
    } catch (error) {
      if (error instanceof DOMException && error.name === "AbortError") return;
      setFeedback("Unable to share");
    }
    window.setTimeout(() => setFeedback("Share"), 2200);
  }

  return (
    <button
      type="button"
      onClick={() => void share()}
      className="focus-ring text-xs text-muted underline underline-offset-4 hover:text-ink"
    >
      {feedback}
    </button>
  );
}
