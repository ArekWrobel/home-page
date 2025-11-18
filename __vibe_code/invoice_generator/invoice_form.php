<form method="post" action="generate_invoice.php">
  <label>Data wystawienia: <input type="date" name="issue_date" required></label><br>
  <label>Imię i nazwisko sprzedawcy: <input type="text" name="seller" required></label><br>
  <label>Adres sprzedawcy (opcjonalnie): <input type="text" name="seller_address"></label><br>
  <label>Dane nabywcy: <input type="text" name="buyer" required></label><br>
  <label>Adres nabywcy: <input type="text" name="buyer_address"></label><br>
  <label>Nazwa usługi: <input type="text" name="service" required></label><br>
  <label>Ilość: <input type="number" name="quantity" min="1" value="1" required></label><br>
  <label>Cena jednostkowa: <input type="number" step="0.01" name="unit_price" required></label><br>
  <!-- <input type="hidden" name="document_number" value="1" type="number">  -->
  <input type="submit" value="Generuj PDF">
</form>
