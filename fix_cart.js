const fs = require('fs');
let content = fs.readFileSync('C:/laragon/www/php/shop/frontend/src/views/Cart.vue', 'utf8');
content = content.replace(/const receiver = ref\(\{ name: '', phone: '', address: '' \}\)\s*\n/, '');
content = content.replace(/async function checkout\(\) \{[\s\S]*?ordering\.value = false\s*\}/, `async function checkout() {
  if (dineType.value === 'takeout') {
    const phone = prompt('外帶請留手機號碼，方便取餐聯繫：');
    if (!phone) return;
    ordering.value = true;
    cartStore.setDine(tableNum.value, dineType.value);
    const items = cartStore.items.map(i => ({ product_id: i.product_id, quantity: i.quantity }));
    const res = await orderStore.placeOrder(items, { name: '', phone, address: '' }, remark.value, cartStore.tableNumber, cartStore.orderType);
    ordering.value = false;
    if (res.success) {
      toastStore.success('訂單已建立！');
      router.push(\`/orders/\${res.data.order_id}\`);
    } else {
      toastStore.error(res.message);
    }
    return;
  }
  ordering.value = true;
  cartStore.setDine(tableNum.value, dineType.value);
  const items = cartStore.items.map(i => ({ product_id: i.product_id, quantity: i.quantity }));
  const res = await orderStore.placeOrder(items, { name: '', phone: '', address: '' }, remark.value, cartStore.tableNumber, cartStore.orderType);
  ordering.value = false;
  if (res.success) {
    toastStore.success('訂單已建立！');
    router.push(\`/orders/\${res.data.order_id}\`);
  } else {
    toastStore.error(res.message);
  }
}`);
fs.writeFileSync('C:/laragon/www/php/shop/frontend/src/views/Cart.vue', content, 'utf8');
console.log('done');