# Parse pdf file to Object 

Chuyển đổi pdf file thành dạng Object Document với các thành phần con 
cũng là các dạng Object : `Component`, `Line`, `Page`, ...

## Flow

```

        Pdf      ---->     XML       ---->      Simple Document      ---->     Perfect Document
                   |                   |                               | 
               pdftohtml           Parse Page                    DetectMargin
                                   Parse Fonts                   FontClassify
                                   Parse Text                    DetectExtraContent
                                                                 MergeComponents
                                                                 DetectColumns
                                                                 DetectToc
                                                                 DetectTable
                                                                 MergeLines
                                                                 DetectHeading

```
  
  
### PdfToHtml

- Sử dụng pdftohtml trong bộ poppler-utils để chuyển đổi file pdf thành xml

### Make simple document

- Bước này đơn giản là parse các dòng xml (Không parse dom) thành các object cơ bản nhất
  - Page
  - Font
  - Text
  
### Processors

Các nước làm Document chuẩn hơn gọi là Process, thứ tự process ảnh hưởng rất nhiều vào đầu ra

Core Process :

- Detect Margin
- FontClassify
- DetectExtraContent
- MergeComponents

#### 1. Detect Margin

Ý tưởng của margin là xác định khung nhỏ nhất chứa toàn bộ các Text Component, margin được lưu lại vào từng trang.

#### 2. FontClassify

Tính toán mức độ phổ biến của các font khác nhau để đưa ra font size phổ biến nhất, các font có font size thuộc dạng phổ 
biến nhất được coi là font chữ thường. Từ đó khi in ra html có thể sử dụng font-size theo % để hiển thị kích thước giống 
pdf hơn

#### 3. DetectExtraContent

Mục này tính toán tìm ra header/footer, extra left/right (chưa làm). Ý tưởng đơn giản là xét các nhóm trang thuộc trang 
chẵn/lẻ. Với mỗi trang trong nhóm, đi từ top xuống dần dần từng khoảng (~22px), xác định text trong khu vực này, so sánh 
sự khác nhau, nếu chỉ khác nhau không quá 2 chỗ, và các ký tự khác nhau chỉ là số hoặc ivx(số la mã) thì coi là phù hợp
khả năng là header, sau đó mở rộng tiếp thêm xuống dưới cho đến khi không đủ điều kiện thì điểm thoả mã trước đó chính 
là giới hạn header. Với footer tương tự nhưng đi từ dưới lên.

Lưu ý :

- Tìm vị trí khác nhau sử dụng FineDiff base từ https://github.com/gorhill/PHP-FineDiff
- "khác nhau không quá 2 chỗ" là do một số tài liệu, phần header/footer bị extract được 2 lần
- Đối với ngỗn ngữ không phải latin, có các ký tự khác biểu diễn số, ví dụ Nhật bản １２３ khác 123 

### 4. MergeComponents
 

## Usage

### Simple

```php
<?php
$parser = new \ThikDev\PdfParser\Parser( $file );
$document = $parser->process();// run pipeline processes
$text = $document->getText();// get text
$html = $document->getHtml();// get html
```

### Add custom process

- Extend `AbstractProcess`
- Add to pipeline by `addProcessBefore`, `addProcessAfter`, or `replaceProcess`

### Add custom Component

- Extend `Component` class and use it in your process class