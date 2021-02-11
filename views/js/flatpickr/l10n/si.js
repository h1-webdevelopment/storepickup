/*
 * The MIT License (MIT)
 * 
 * Copyright (c) 2019 Gregory Petrosyan
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, 
 * and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 * 
 * flatpickr v4.6.4,, @license MIT 
 */

(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports) :
  typeof define === 'function' && define.amd ? define(['exports'], factory) :
  (global = global || self, factory(global.si = {}));
}(this, (function (exports) { 'use strict';

  var fp = typeof window !== "undefined" && window.flatpickr !== undefined
      ? window.flatpickr
      : {
          l10ns: {},
      };
  var Sinhala = {
      weekdays: {
          shorthand: ["ඉ", "ස", "අ", "බ", "බ්‍ර", "සි", "සෙ"],
          longhand: [
              "ඉරිදා",
              "සඳුදා",
              "අඟහරුවාදා",
              "බදාදා",
              "බ්‍රහස්පතින්දා",
              "සිකුරාදා",
              "සෙනසුරාදා",
          ],
      },
      months: {
          shorthand: [
              "ජන",
              "පෙබ",
              "මාර්",
              "අප්‍රේ",
              "මැයි",
              "ජුනි",
              "ජූලි",
              "අගෝ",
              "සැප්",
              "ඔක්",
              "නොවැ",
              "දෙසැ",
          ],
          longhand: [
              "ජනවාරි",
              "පෙබරවාරි",
              "මාර්තු",
              "අප්‍රේල්",
              "මැයි",
              "ජුනි",
              "ජූලි",
              "අගෝස්තු",
              "සැප්තැම්බර්",
              "ඔක්තෝබර්",
              "නොවැම්බර්",
              "දෙසැම්බර්",
          ],
      },
      time_24hr: true,
  };
  fp.l10ns.si = Sinhala;
  var si = fp.l10ns;

  exports.Sinhala = Sinhala;
  exports.default = si;

  Object.defineProperty(exports, '__esModule', { value: true });

})));
