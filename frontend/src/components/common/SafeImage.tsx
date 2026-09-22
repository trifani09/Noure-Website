"use client";
import Image, { type ImageProps } from "next/image";
import { useState } from "react";
type SafeImageProps = ImageProps & { fallbackSrc?: ImageProps["src"] };

export function SafeImage({
  alt,
  onError,
  src,
  fallbackSrc,
  ...props
}: SafeImageProps) {
  const [failed, setFailed] = useState(false);
  const [usingFallback, setUsingFallback] = useState(false);
  if (failed)
    return (
      <>
        <div
          aria-hidden
          className="absolute inset-0 bg-[linear-gradient(135deg,#f8f5ef,#ded4ca_55%,#b7a79f)]"
        >
          <span className="absolute inset-0 grid place-items-center font-serif text-2xl tracking-[.25em] text-espresso/50">
            N
          </span>
        </div>
        {alt && <span className="sr-only">{alt}</span>}
      </>
    );
  return (
    <Image
      {...props}
      src={usingFallback && fallbackSrc ? fallbackSrc : src}
      alt={alt}
      onError={(event) => {
        if (fallbackSrc && !usingFallback) setUsingFallback(true);
        else setFailed(true);
        onError?.(event);
      }}
    />
  );
}
