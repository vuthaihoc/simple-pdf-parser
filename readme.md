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