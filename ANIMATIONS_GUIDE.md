# FlowForm - Professional Animation Guide

## Overview
FlowForm now includes a comprehensive suite of professional animations and smooth transitions. All animations respect user preferences for reduced motion and are optimized for performance.

---

## Animation Files

### 1. **animations.css** (`assets/css/animations.css`)
Contains all CSS animation definitions and keyframes:
- Page entrance animations
- Card slide animations
- Button and interactive animations
- Form input animations
- Loading and progress animations
- Smooth transitions and hover effects
- Staggered list animations
- Scroll-triggered animations

### 2. **animations.js** (`assets/js/animations.js`)
JavaScript module that automatically triggers animations on:
- Page load (card stagger animations)
- Scroll events (fade-in on scroll)
- Button interactions (ripple effects, hover)
- Form submissions (loading states)
- Dynamic content (automatic animation on new elements)

---

## Available Animations

### Page Entrance Animations

#### `pageFadeIn` - Smooth page load
```css
animation: pageFadeIn 0.4s ease-out both;
```

#### `pageSlideIn` - Slide from right
```css
animation: pageSlideIn 0.4s ease-out both;
```

#### `fadeIn` - Simple fade
```css
animation: fadeIn 0.3s ease both;
```

---

### Card Animations

#### `cardSlideUp` - Card slides up from bottom
```css
animation: cardSlideUp 0.5s ease-out both;
```
**Use for:** Dashboard cards, form panels, content cards

#### `cardSlideLeft` - Card slides from left
```css
animation: cardSlideLeft 0.5s ease-out both;
```

#### `cardScaleIn` - Card scales from small to normal
```css
animation: cardScaleIn 0.3s ease both;
```

#### `cardBounceIn` - Card bounces in
```css
animation: cardBounceIn 0.5s ease both;
```

---

### Button Animations

#### `buttonHoverFloat` - Button floats up on hover
```css
animation: buttonHoverFloat 0.3s ease forwards;
```

#### `buttonPulse` - Button pulsates with shadow
```css
animation: buttonPulse 2s ease-in-out infinite;
```

#### `buttonRipple` - Ripple effect on click
```javascript
// Automatically applied by animations.js
```

---

### Form Input Animations

#### `inputFocus` - Focus ring animation
```css
animation: inputFocus 0.3s ease both;
```

#### `inputSuccess` - Success state with glow
```css
animation: inputSuccess 0.5s ease both;
```

#### `inputError` - Error shake animation
```css
animation: inputError 0.4s ease both;
```

---

### Loading & Progress Animations

#### `spin` - Rotation animation
```css
animation: spin 1s linear infinite;
```

#### `pulse` - Opacity pulse
```css
animation: pulse 2s ease-in-out infinite;
```

#### `bounce` - Up and down bounce
```css
animation: bounce 1s ease infinite;
```

#### `shimmer` - Shimmer/skeleton loading effect
```css
animation: skeletonShimmer 2s infinite;
```

---

### Notification Animations

#### `slideInTop` - Alert slides down from top
```css
animation: slideInTop 0.3s ease forwards;
```

#### `slideOutTop` - Alert slides up and out
```css
animation: slideOutTop 0.3s ease forwards;
```

#### `slideInRight` - Slide in from right
```css
animation: slideInRight 0.3s ease forwards;
```

---

### Menu & Dropdown Animations

#### `dropdownFadeIn` - Dropdown fades in
```css
animation: dropdownFadeIn 0.2s ease forwards;
```

#### `menuSlideIn` - Menu slides from left
```css
animation: menuSlideIn 0.3s ease forwards;
```

---

### Background Animations

#### `gradientShift` - Animated gradient
```css
animation: gradientShift 3s ease infinite;
```

#### `floatAnimation` - Gentle floating motion
```css
animation: floatAnimation 3s ease-in-out infinite;
```

#### `floatSlow` - Slower floating motion
```css
animation: floatSlow 4s ease-in-out infinite;
```

---

### List Animations

#### `listItemFadeIn` - Staggered list fade-in
```css
animation: listItemFadeIn 0.5s ease both;
```

---

### Modal Animations

#### `modalFadeIn` - Overlay fade in
```css
animation: modalFadeIn 0.3s ease both;
```

#### `modalSlideUp` - Modal slides up
```css
animation: modalSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
```

---

## Utility Classes

Apply these classes to any element for quick animations:

### Entrance Animations
- `.fade-enter` - Fade in
- `.slide-up-enter` - Slide up
- `.scale-enter` - Scale in
- `.bounce-enter` - Bounce in
- `.slide-top-enter` - Slide from top
- `.slide-right-enter` - Slide from right
- `.dropdown-enter` - Dropdown fade
- `.menu-enter` - Menu slide
- `.modal-enter` - Modal slide up

### Continuous Animations
- `.spinner` - Spinning loader
- `.spinner-reverse` - Reverse spin
- `.pulse-animate` - Pulsing effect
- `.bounce-animate` - Bouncing effect
- `.button-pulse` - Button pulsate
- `.text-glow` - Glowing text effect
- `.badge-pulse` - Badge pulsate

### Scroll Triggered
- `.animate-on-scroll` - Animate when in view
- `.animate-on-scroll.fade-in` - Fade on scroll
- `.animate-on-scroll.slide-up` - Slide up on scroll
- `.animate-on-scroll.slide-left` - Slide left on scroll

---

## Usage Examples

### Example 1: Animated Card
```html
<div class="panel-card">
    <!-- Content here -->
    <!-- Automatically animated on page load -->
</div>
```

### Example 2: Animated Alert
```html
<div class="alert alert-success slide-top-enter">
    <span>Form submitted successfully!</span>
</div>
```

### Example 3: Staggered List Items
```html
<div class="list-stack">
    <div class="list-row stagger-item">Item 1</div>
    <div class="list-row stagger-item">Item 2</div>
    <div class="list-row stagger-item">Item 3</div>
</div>
```

### Example 4: Loading Button
```html
<button class="btn-primary loading">
    <span class="spinner"></span>
    Processing...
</button>
```

### Example 5: Scroll-Triggered Animation
```html
<div class="metric-card animate-on-scroll slide-up">
    <strong>42</strong>
    <span>Total Forms</span>
</div>
```

---

## JavaScript API

### Animate Single Element
```javascript
// Animate an element with a specific animation
animateElement(element, 'cardSlideUp', 400).then(() => {
    console.log('Animation complete!');
});
```

### Stagger Animate Group
```javascript
// Animate multiple elements with stagger effect
staggerAnimate('.list-row', 'listItemFadeIn', 50);
```

### Show/Hide Loading
```javascript
// Show loading spinner
showLoading();

// Hide loading spinner
hideLoading();
```

---

## Performance Optimization

### Automatic Optimizations
- Animations respect `prefers-reduced-motion` setting
- GPU-accelerated transforms (no layout thrashing)
- Debounced scroll animations
- Efficient MutationObserver for dynamic content

### Best Practices
1. **Use transform and opacity** for the best performance
2. **Avoid animating layout properties** like width/height
3. **Use the provided utility classes** instead of custom CSS
4. **Batch animations** with stagger effects
5. **Test on low-end devices** for performance

### Disable Animations
If needed, animations automatically disable for users who prefer reduced motion:
```css
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}
```

---

## Customization

### Modify Animation Duration
```css
/* In your custom CSS */
@keyframes cardSlideUp {
    /* Change timing here */
    animation: cardSlideUp 0.8s ease-out both; /* Make slower */
}
```

### Create Custom Animations
```css
@keyframes myCustomAnimation {
    from {
        opacity: 0;
        transform: translate(-20px, -20px);
    }
    to {
        opacity: 1;
        transform: translate(0, 0);
    }
}

.my-custom-class {
    animation: myCustomAnimation 0.5s ease forwards;
}
```

### Adjust Animation Delay
```css
.stagger-item:nth-child(1) { animation-delay: 0.1s; }
.stagger-item:nth-child(2) { animation-delay: 0.2s; }
.stagger-item:nth-child(3) { animation-delay: 0.3s; }
```

---

## Browser Support

All animations use standard CSS3 and are supported in:
- Chrome/Edge 26+
- Firefox 16+
- Safari 9+
- iOS Safari 9+
- Android Browser 4.4+

---

## Animation Timeline

### Page Load Sequence
1. **0ms** - Body fades in (0.4s)
2. **50ms** - Hero section scales in (0.6s)
3. **100ms** - Cards begin sliding up (staggered by 80ms each)
4. **500ms+** - Page fully rendered with all animations complete

### Form Submission Sequence
1. Submit button becomes loading state
2. Button pulses with animation
3. Success/error alert slides in from top
4. Form field success animation plays

### Navigation Sequence
1. Link receives hover transform
2. Page fades during transition
3. New page fades in on load

---

## Troubleshooting

### Animations Not Playing
1. Check if `animations.css` is loaded
2. Check if `animations.js` is loaded
3. Verify browser supports CSS animations
4. Check browser console for errors

### Performance Issues
1. Check for too many simultaneous animations
2. Use GPU-accelerated properties (transform, opacity)
3. Reduce animation duration
4. Disable animations in `prefers-reduced-motion`

### Animations Too Fast/Slow
1. Adjust duration in CSS animation definition
2. Adjust easing function (ease, ease-in, ease-out, ease-in-out, cubic-bezier)
3. Check for CSS animation property conflicts

---

## Files Modified

1. ✅ `assets/css/style.css` - Enhanced with better animations
2. ✅ `assets/css/animations.css` - New comprehensive animations file
3. ✅ `assets/js/animations.js` - New JavaScript animation handler
4. ✅ `app/views/layouts/main.php` - Added animation links
5. ✅ `app/views/layouts/auth.php` - Added animation links

---

## Next Steps

To further enhance animations:
1. Add page transition animations between routes
2. Create animated loading states for AJAX requests
3. Add micro-interactions for form validation
4. Implement gesture animations for mobile
5. Create animated data visualizations

---

**Last Updated:** April 26, 2026
**Status:** Production Ready ✅
